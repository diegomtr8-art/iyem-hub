<?php

namespace App\Services\Modulos;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as Respuesta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cliente HTTP de las APIs de reportes de los módulos con tablero.
 *
 * Server-to-server, siempre: el token vive en el `.env` del ERP y no sale de
 * esta clase. El navegador habla con el ERP; el ERP habla con el módulo.
 *
 * Reglas:
 *
 * - 5 s de espera (2 para conectar). La dirección no va a esperar quince.
 * - Caché de 10 minutos por módulo + endpoint + parámetros. Son números de
 *   dirección, no un marcador en vivo, y sin caché cinco personas mirando el
 *   tablero son cinco consultas pesadas en el módulo.
 * - Un reintento, solo si falló la red o el módulo respondió 5xx. Un 403, un
 *   409 o un 429 no van a mejorar y encima gastan el límite de peticiones.
 * - Cada llamada queda en el log: módulo, endpoint, milisegundos y código.
 * - Nunca se manda `X-App-Version`: la API de Nódico solo corta a quien la
 *   manda vieja, y el día que suban la mínima de la app móvil el tablero del
 *   ERP se caería con un 409.
 */
class ClienteDeModulo
{
    public const SEGUNDOS_DE_ESPERA = 5;

    public const SEGUNDOS_DE_CONEXION = 2;

    public const MINUTOS_DE_CACHE = 10;

    /** Cuánto se recuerda la hora del último dato bueno, para el aviso de falla. */
    private const DIAS_DEL_ULTIMO_DATO = 7;

    private const INTENTOS = 2;

    /**
     * Módulos que ya no contestaron por red en esta petición. Un tablero hace
     * varias llamadas seguidas; si la primera no llegó, esperar el timeout en
     * cada una de las demás solo alarga el aviso.
     *
     * @var array<string, Falla>
     */
    private array $sinRed = [];

    /**
     * Datos del módulo, sin la envoltura `data`.
     *
     * Con `$cachear = false` no se guarda nada: es para las listas con datos
     * personales, que no tienen por qué quedarse en la caché del ERP.
     */
    public function consultar(string $slug, string $endpoint, array $parametros = [], bool $cachear = true): RespuestaDeModulo
    {
        $clave = $this->claveDeCache($slug, $endpoint, $parametros);

        if ($cachear && ($guardado = Cache::get($clave))) {
            return RespuestaDeModulo::exito($guardado['datos'], $guardado['obtenido_en']);
        }

        $respuesta = $this->pedir($slug, $endpoint, $parametros);

        if ($respuesta instanceof Falla) {
            return RespuestaDeModulo::fallo($respuesta->conUltimoDato(Cache::get("{$clave}:ultimo")));
        }

        $datos = $respuesta->json('data');

        if (! is_array($datos)) {
            return RespuestaDeModulo::fallo(new Falla(
                Falla::RESPUESTA,
                "{$this->nombre($slug)} respondió algo que el ERP no sabe leer.",
                'La API del módulo cambió de forma o no es la que indica `api_base`. Revisa el registro del ERP.',
            ));
        }

        $obtenidoEn = now()->toIso8601String();

        if ($cachear) {
            Cache::put($clave, ['datos' => $datos, 'obtenido_en' => $obtenidoEn], now()->addMinutes(self::MINUTOS_DE_CACHE));
            Cache::put("{$clave}:ultimo", $obtenidoEn, now()->addDays(self::DIAS_DEL_ULTIMO_DATO));
        }

        return RespuestaDeModulo::exito($datos, $obtenidoEn);
    }

    /**
     * Un archivo del módulo (CSV), transmitido al navegador sin guardarlo y
     * sin caché: el navegador nunca recibe una URL del módulo con el token.
     */
    public function descargar(string $slug, string $endpoint, array $parametros, string $nombreArchivo): StreamedResponse|Falla
    {
        $respuesta = $this->pedir($slug, $endpoint, $parametros, transmitir: true);

        if ($respuesta instanceof Falla) {
            return $respuesta;
        }

        $cuerpo = $respuesta->toPsrResponse()->getBody();

        return response()->streamDownload(function () use ($cuerpo) {
            while (! $cuerpo->eof()) {
                echo $cuerpo->read(8192);
                flush();
            }
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pedir(string $slug, string $endpoint, array $parametros, bool $transmitir = false): Respuesta|Falla
    {
        $base = config("modulos.{$slug}.api_base");
        $token = config("services.modulos.{$slug}.token");

        if (! $base || ! $token) {
            return new Falla(
                Falla::SIN_CONFIGURAR,
                "La integración con {$this->nombre($slug)} no está configurada.",
                "Falta `api_base` en config/modulos.php o el token en el .env del ERP (services.modulos.{$slug}.token).",
            );
        }

        if (isset($this->sinRed[$slug])) {
            return $this->sinRed[$slug];
        }

        $url = rtrim($base, '/').'/'.ltrim($endpoint, '/');

        for ($intento = 1; $intento <= self::INTENTOS; $intento++) {
            $inicio = microtime(true);

            try {
                $respuesta = $this->peticion($token, $transmitir)->get($url, $parametros);
            } catch (ConnectionException $e) {
                $this->registrar($slug, $endpoint, $inicio, 'sin_red', $intento, $e->getMessage());

                if ($intento < self::INTENTOS) {
                    continue;
                }

                return $this->sinRed[$slug] = new Falla(
                    Falla::SIN_RED,
                    "No se pudo consultar {$this->nombre($slug)}.",
                    'El módulo no respondió a tiempo. Puede ser algo pasajero.',
                    reintentable: true,
                );
            }

            $this->registrar($slug, $endpoint, $inicio, $respuesta->status(), $intento);

            if ($respuesta->successful()) {
                return $respuesta;
            }

            if ($this->valeReintentar($respuesta) && $intento < self::INTENTOS) {
                continue;
            }

            return $this->fallaDe($slug, $respuesta);
        }
    }

    private function peticion(string $token, bool $transmitir): PendingRequest
    {
        return Http::withToken($token)
            ->acceptJson()
            ->withUserAgent('IYEM-ERP')
            ->timeout(self::SEGUNDOS_DE_ESPERA)
            ->connectTimeout(self::SEGUNDOS_DE_CONEXION)
            ->withOptions($transmitir ? ['stream' => true] : []);
    }

    /** 5xx sí; 503 de mantenimiento no, porque es una pausa deliberada. */
    private function valeReintentar(Respuesta $respuesta): bool
    {
        return $respuesta->serverError() && $respuesta->json('codigo') !== 'mantenimiento';
    }

    private function fallaDe(string $slug, Respuesta $respuesta): Falla
    {
        $nombre = $this->nombre($slug);
        $codigo = $respuesta->json('codigo');

        return match (true) {
            $respuesta->status() === 401 => new Falla(
                Falla::NO_AUTENTICADO,
                "La integración con {$nombre} no está autenticada.",
                'El token del ERP no existe, fue revocado o caducó por falta de uso. Hay que emitir uno nuevo para la cuenta de servicio y ponerlo en el .env del ERP.',
            ),
            $respuesta->status() === 403 => new Falla(
                Falla::SIN_PERMISO,
                "La integración con {$nombre} no tiene permiso para consultar reportes.",
                'Revisa que la cuenta de servicio del ERP en el módulo siga activa y conserve el permiso `ver-reportes`.',
            ),
            $respuesta->status() === 429 => new Falla(
                Falla::LIMITE,
                "Demasiadas consultas a {$nombre}. Intenta en un momento.",
            ),
            $respuesta->status() === 503 && $codigo === 'mantenimiento' => new Falla(
                Falla::MANTENIMIENTO,
                // El mensaje del módulo, tal cual: ellos saben por qué está en pausa.
                (string) ($respuesta->json('message') ?: "{$nombre} está en mantenimiento."),
                reintentable: true,
            ),
            $respuesta->status() === 422 => new Falla(
                Falla::PERIODO,
                "{$nombre} no aceptó el periodo: ".($this->primerError($respuesta) ?? 'revisa las fechas.'),
            ),
            $respuesta->serverError() => new Falla(
                Falla::SIN_RED,
                "No se pudo consultar {$nombre}.",
                "El módulo respondió con un error ({$respuesta->status()}).",
                reintentable: true,
            ),
            default => new Falla(
                Falla::RESPUESTA,
                "{$nombre} rechazó la consulta.",
                "Respuesta {$respuesta->status()}".($codigo ? " ({$codigo})" : '').'. Revisa el registro del ERP.',
            ),
        };
    }

    private function primerError(Respuesta $respuesta): ?string
    {
        $errores = $respuesta->json('errors');

        return is_array($errores) ? (collect($errores)->flatten()->first() ?: null) : $respuesta->json('message');
    }

    private function registrar(string $slug, string $endpoint, float $inicio, int|string $codigo, int $intento, ?string $motivo = null): void
    {
        Log::info('Consulta a la API de un módulo.', array_filter([
            'modulo' => $slug,
            'endpoint' => $endpoint,
            'ms' => (int) round((microtime(true) - $inicio) * 1000),
            'codigo' => $codigo,
            'intento' => $intento,
            'motivo' => $motivo,
        ], fn ($valor) => $valor !== null));
    }

    private function claveDeCache(string $slug, string $endpoint, array $parametros): string
    {
        ksort($parametros);

        return "modulos:{$slug}:".sha1($endpoint.'?'.http_build_query($parametros));
    }

    private function nombre(string $slug): string
    {
        return (string) config("modulos.{$slug}.nombre", $slug);
    }
}
