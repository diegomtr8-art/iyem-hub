# Módulos con tablero dentro del ERP

Un módulo externo puede tener **dos puertas** desde el ERP:

1. **Su tablero dentro del ERP** (`/modulos/{slug}`): indicadores y reportes para la dirección, traídos por API y pintados con la identidad del ERP. Solo lectura.
2. **"Ir al sitio"**: sale al dominio del módulo para operar, pasando por `dashboard.acceder` para dejar registro.

El piloto es **Nódico 2.0** (`coworkhub`) contra `prueba.nodico.com.mx`.

## Cómo funciona

```
Navegador ──► ERP ──► API de reportes del módulo
              │
              ├─ GET /modulos/{slug}                    marco Inertia (sin llamar al módulo)
              ├─ GET /modulos/{slug}/datos              JSON: resumen + secciones
              ├─ GET /modulos/{slug}/informes/{x}.csv   CSV transmitido por el ERP
              ├─ GET /modulos/{slug}/personas           marco de la vista de datos personales
              └─ GET /modulos/{slug}/personas/datos     JSON: listas con nombres (sin caché)
```

| Pieza | Qué hace |
|---|---|
| `config/modulos.php` → `tablero`, `api_base`, `entorno_de_prueba` | Declara que el módulo tiene tablero y dónde está su API. |
| `config/services.php` → `modulos.{slug}.token` | El token. Solo lo lee `ClienteDeModulo`; nunca viaja al navegador. |
| `App\Services\Modulos\ClienteDeModulo` | HTTP: token, 5 s de espera (2 para conectar), caché de 10 min, un reintento solo por red o 5xx, registro de cada llamada. Nunca manda `X-App-Version`. |
| `App\Services\Modulos\AdaptadorDeModulo` | Contrato: traduce la API del módulo a `Resumen`, `Indicador` y `Seccion`. |
| `App\Services\Modulos\RegistroDeAdaptadores` | Clave del adaptador → clase. |
| `App\Services\CatalogoModulos::tieneTablero()` | Si se pinta el tablero. **Independiente de `navegable`**, que solo dice si se puede salir al sitio. |
| `ModuloController` | Permisos, periodo, registro en `accesos`. |
| `Pages/Modulos/Tablero.vue` y `Components/Modulo/*` | Genéricos: solo conocen unidades (`porcentaje`, `moneda`, `horas`…), nunca campos de un módulo. |

### Permisos

- `ver-{slug}` **y** `ver-modulo-tablero` para el tablero. Ver la tarjeta no es ver los ingresos.
- `ver-modulo-datos-personales` para las listas con nombres y sus CSV. Hoy solo Super Admin.

### Registro

Todo queda en `accesos` con `detalle`: `tablero`, `informe: ocupacion.csv`, `datos-personales: miembros_en_riesgo, inasistencias`. El módulo registra esas consultas a nombre de la cuenta de servicio del ERP; quién preguntó de verdad solo queda aquí.

### Cuando el módulo no responde

Nunca un cero: cada parte trae una `falla` (`sin_red`, `sin_permiso`, `no_autenticado`, `mantenimiento`, `limite`, `periodo`, `respuesta`, `sin_configurar`) y la página la muestra con `AvisoDeFalla`. Si el módulo no contestó por red, las demás llamadas de esa misma petición ya no esperan otro timeout.

## Agregar un módulo (ejemplo: Impúlsate)

**Crear:**

1. `app/Services/Modulos/Adaptadores/ImpulsateAdaptador.php`, que implemente `AdaptadorDeModulo` traduciendo su API a `Indicador` y `Seccion`.
2. Pruebas del adaptador con `Http::fake()`.

**Cambiar:**

1. `RegistroDeAdaptadores::ADAPTADORES`: `'impulsate' => ImpulsateAdaptador::class`.
2. `config/modulos.php` → `impulsate`: `tablero`, `api_base`, `entorno_de_prueba`.
3. `config/services.php` → `modulos.impulsate.token`, y la variable en `.env.example`.

**No se toca:** `Tablero.vue`, `DatosPersonales.vue`, `Components/Modulo/*`, `ModuloController`, `ClienteDeModulo`, las rutas ni `TarjetaModulo.vue`.

Si una unidad nueva hace falta (por ejemplo, "kilómetros"), se agrega a `Indicador` y a `formato.js`, no a la página.
