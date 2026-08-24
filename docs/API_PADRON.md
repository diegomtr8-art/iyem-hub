# API del Padrón Central — v1

Esta API es el mecanismo por el que los sistemas del IYEM comparten una
misma idea de quién es cada persona **sin migrar sus bases de datos**.

Cada sistema satélite conserva la suya. Lo único que agrega es una columna
`persona_id`, y la llena preguntándole al hub.

- **Base:** `https://iyemyucatan.com/api/v1`
- **Formato:** JSON en ambos sentidos. Manda `Accept: application/json`.
- **Zona horaria:** todas las fechas salen en ISO 8601 con huso.

---

## 1. Autenticación

Se autentica el **sistema**, no la persona que lo opera. Si mañana quien
configuró la integración se va del instituto, la integración sigue viva.

Cada sistema tiene su registro en `sistemas_integrados` y su token de
Sanctum. El token se emite desde el servidor del hub:

```bash
php artisan sistemas:registrar crea \
    --nombre="CREA — Créditos" \
    --url=https://crea.iyemyucatan.com \
    --contacto=informatica@iyemyucatan.com \
    --habilidades=padron:leer,padron:escribir,eventos:escribir
```

El comando imprime el token **una sola vez**. El hub guarda solo su hash:
si se pierde, no se recupera, se emite otro (`--revocar` invalida los
anteriores).

Guárdalo en el `.env` del sistema satélite:

```env
IYEM_PADRON_URL=https://iyemyucatan.com/api/v1
IYEM_PADRON_TOKEN=17|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Y mándalo en cada petición:

```
Authorization: Bearer 17|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Habilidades

El token lleva la lista de lo que ese sistema puede hacer. Pide solo lo que
uses: un módulo que únicamente consulta no tiene por qué poder escribir.

| Habilidad | Permite |
|---|---|
| `padron:leer` | `GET /personas`, `/personas/buscar`, `/personas/{id}`, `/personas/{id}/vinculos` |
| `padron:escribir` | `POST /personas`, `PUT /personas/{id}`, `POST /personas/resolver` |
| `eventos:escribir` | `POST /eventos` |

Sin la habilidad correcta, la respuesta es **403**, no 401: el token es
válido, lo que falta es el permiso.

### Límite de peticiones

**120 por minuto y por sistema.** La cuenta va por token, no por IP: varios
módulos comparten servidor en Hostinger, y limitar por IP haría que el
tráfico de CREA agotara la cuota de Impúlsate.

Al pasarse, el hub responde **429** con la cabecera `Retry-After`.

---

## 2. Referencia rápida

| Método | Ruta | Habilidad | Para qué |
|---|---|---|---|
| `GET` | `/salud` | — | Health check |
| `GET` | `/personas` | `padron:leer` | Listado paginado con filtros |
| `GET` | `/personas/buscar?q=` | `padron:leer` | Búsqueda libre |
| `GET` | `/personas/{id}` | `padron:leer` | Ficha completa |
| `GET` | `/personas/{id}/vinculos` | `padron:leer` | Registros en todos los módulos |
| `POST` | `/personas` | `padron:escribir` | Alta (idempotente por CURP) |
| `PUT` | `/personas/{id}` | `padron:escribir` | Actualización parcial |
| `POST` | `/personas/resolver` | `padron:escribir` | **Resuelve o crea** |
| `POST` | `/eventos` | `eventos:escribir` | Reportar un hecho |

---

## 3. El endpoint que importa: `POST /personas/resolver`

Si solo vas a integrar una cosa, que sea esta.

Le mandas lo que sepas de la persona y te devuelve el `persona_id` del
padrón central: el de quien ya existe, o el de quien acaba de crear.

### Orden de coincidencia

De más a menos confiable. El primero que acierta gana:

| # | Criterio | Por qué en ese lugar |
|---|---|---|
| 1 | **CURP** | Identificador único ante RENAPO |
| 2 | **RFC** | Único ante el SAT, pero una persona moral y su representante pueden compartir contacto |
| 3 | **Correo** | Único en la tabla, aunque se comparte entre familias y negocios |
| 4 | **Teléfono + nombre** | El teléfono solo no basta: en Yucatán es común que varios miembros de un negocio den el mismo número |

En el cuarto criterio, el nombre debe parecerse al menos un **82 %**,
comparando sin acentos, sin mayúsculas y sin importar el orden de las
palabras (que cambia según si quien capturó puso primero el nombre o los
apellidos). Ese umbral tolera un segundo apellido faltante sin llegar a
confundir a dos hermanos.

### Qué hace con los datos que le mandas

Si encuentra a la persona, **completa los huecos pero no pisa nada**. Si el
padrón ya tiene un teléfono y tú mandas otro, el del padrón se queda: ese
conflicto lo resuelve una persona desde la ficha, no una llamada a la API.
Los campos que estaban vacíos sí se llenan, y cada uno queda registrado en
la auditoría con tu módulo como origen.

### Ejemplo

```bash
curl -X POST https://iyemyucatan.com/api/v1/personas/resolver \
  -H "Authorization: Bearer $IYEM_PADRON_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
        "curp": "POCC900101MZZTNN01",
        "nombre": "Candelaria Poot Canul",
        "telefono": "9991234567",
        "municipio": "Mérida"
      }'
```

Respuesta cuando ya existía (**200**):

```json
{
  "data": { "id": 412, "nombre_completo": "Candelaria Poot Canul", "...": "..." },
  "meta": {
    "persona_id": 412,
    "creada": false,
    "coincidio_por": "curp"
  }
}
```

Respuesta cuando la creó (**201**): igual, con `"creada": true` y
`"coincidio_por": null`.

`coincidio_por` puede ser `curp`, `rfc`, `email`, `telefono_y_nombre` o
`null`. Vale la pena registrarlo del lado del módulo: si de pronto muchas
coincidencias son por `telefono_y_nombre`, es señal de que están capturando
sin CURP.

### Reglas de validación

- `curp` — 18 caracteres, estructura de RENAPO
- `rfc` — 12 (moral) o 13 (física) caracteres
- `email` — correo válido
- `telefono` — se limpia solo: puedes mandar `999 123 4567` o `(999) 123-4567`
- `nombre` — **obligatorio si no mandas CURP, RFC ni correo**

---

## 4. Cómo integrar tu módulo

Tres pasos.

### Paso 1 — Agrega la columna

```php
Schema::table('tu_tabla', function (Blueprint $table) {
    $table->unsignedBigInteger('persona_id')->nullable()->after('id');
    $table->index('persona_id');
});
```

Nullable a propósito: los registros históricos se van resolviendo poco a
poco, no de golpe.

### Paso 2 — Resuelve al capturar

```php
$respuesta = Http::withToken(config('services.padron.token'))
    ->acceptJson()
    ->post(config('services.padron.url').'/personas/resolver', [
        'curp'     => $solicitud->curp,
        'nombre'   => $solicitud->nombre_completo,
        'telefono' => $solicitud->telefono,
    ]);

$solicitud->update([
    'persona_id' => $respuesta->json('meta.persona_id'),
]);
```

### Paso 3 — Reporta lo que pasa

```php
Http::withToken(config('services.padron.token'))
    ->acceptJson()
    ->post(config('services.padron.url').'/eventos', [
        'persona_id'         => $solicitud->persona_id,
        'tipo'               => 'solicitud_aprobada',
        'titulo'             => 'Solicitud de crédito aprobada',
        'detalle'            => 'Crédito semilla por $25,000',
        'estado'             => 'aprobada',
        'referencia_externa' => "CREA-{$solicitud->id}",
    ]);
```

**Manda siempre `referencia_externa`.** Es lo que hace el envío idempotente:
si tu petición se corta y reintentas, el hub actualiza el evento en lugar de
duplicar la línea de tiempo de esa persona.

---

## 5. Resto de los endpoints

### `GET /salud`

Único endpoint sin token: un semáforo que solo enciende para quien ya tiene
credenciales no sirve para diagnosticar una caída.

```bash
curl https://iyemyucatan.com/api/v1/salud
```

```json
{
  "estado": "en_linea",
  "servicio": "IYEM Hub — Padrón Central",
  "version_api": "v1",
  "base_de_datos": "en_linea",
  "personas": 12483,
  "hora": "2026-08-24T11:42:03-06:00"
}
```

Responde **503** con `"estado": "degradado"` si la base no contesta.

### `GET /personas`

```bash
curl "https://iyemyucatan.com/api/v1/personas?municipio=Valladolid&por_pagina=50" \
  -H "Authorization: Bearer $IYEM_PADRON_TOKEN" \
  -H "Accept: application/json"
```

| Parámetro | Valores |
|---|---|
| `municipio` | Nombre exacto del municipio |
| `estado_persona` | `activa`, `inactiva`, `bloqueada` |
| `etiqueta` | Una etiqueta del padrón |
| `modulo_origen` | Slug del módulo que dio de alta a la persona |
| `actualizadas_desde` | Fecha ISO. **Úsalo para sincronizar**: trae solo lo que cambió |
| `por_pagina` | 1 a 100. Por omisión 25 |

Para una sincronización nocturna, guarda la hora de la última corrida y
pásala en `actualizadas_desde`. Recorrer el padrón entero cada noche es
desperdicio.

### `GET /personas/buscar`

```bash
curl "https://iyemyucatan.com/api/v1/personas/buscar?q=Candelaria" \
  -H "Authorization: Bearer $IYEM_PADRON_TOKEN" \
  -H "Accept: application/json"
```

Busca en nombre, correo, CURP, RFC y ambos teléfonos. Mínimo 3 caracteres.
Es para el buscador de tu módulo; para sincronizar usa `/personas`.

### `GET /personas/{id}/vinculos`

Todo lo que la persona tiene en los demás módulos, más su línea de tiempo
unificada. Es lo que alimenta la ficha 360° del hub.

```bash
curl https://iyemyucatan.com/api/v1/personas/412/vinculos \
  -H "Authorization: Bearer $IYEM_PADRON_TOKEN" \
  -H "Accept: application/json"
```

```json
{
  "data": {
    "persona_id": 412,
    "vinculos": [
      { "slug": "crea", "nombre": "CREA", "total": 2, "descripcion": "2 solicitudes de crédito" }
    ],
    "linea_de_tiempo": [
      {
        "modulo": "crea",
        "modulo_nombre": "CREA",
        "titulo": "Solicitud de crédito · Semilla",
        "detalle": "Por $25,000.00",
        "estado": "aprobada",
        "fecha": "2026-06-14T10:22:00-06:00"
      }
    ]
  }
}
```

### `POST /personas`

Alta directa. **Idempotente por CURP**: si ya existe alguien con esa CURP,
responde **200** con la persona existente y `"creada": false`, en vez de
fallar. Así un reintento tras un timeout no duplica a nadie.

Si no sabes si la persona existe —que es lo normal—, usa `/resolver` en vez
de este endpoint.

### `PUT /personas/{id}`

Actualización parcial: manda solo los campos que cambian. **Cada campo
modificado queda en `personas_auditorias`** con tu módulo como origen, el
valor anterior y el nuevo. Es lo que permite reconstruir después quién
cambió qué.

---

## 6. Errores

| Código | Qué pasó | Qué hacer |
|---|---|---|
| `401` | Token ausente, mal formado o revocado | Revisa la cabecera `Authorization` |
| `403` | Token válido, sin la habilidad necesaria | Pide un token nuevo con la habilidad |
| `404` | La persona no existe o fue eliminada | No reintentes |
| `422` | Falló la validación | Corrige los datos; **no reintentes igual** |
| `429` | Pasaste las 120 por minuto | Espera lo que diga `Retry-After` |
| `503` | El hub está degradado | Reintenta con espera creciente |

Los errores de validación traen el detalle campo por campo:

```json
{
  "message": "El campo curp no tiene un formato válido.",
  "errors": {
    "curp": ["El campo curp no tiene un formato válido."]
  }
}
```

**Reintenta** en 429 y 503, con espera creciente. **No reintentes** en 401,
403, 404 ni 422: el mismo envío va a fallar igual.

---

## 7. Lo que la API no hace

Dicho de frente, para que nadie lo espere:

- **No borra personas.** El padrón es acumulativo; las bajas se marcan como
  `estado_persona = "inactiva"` desde la interfaz del hub.
- **No fusiona duplicados.** Eso lo decide un Super Admin desde
  `/padron/duplicados`, porque fusionar dos expedientes es una decisión, no
  una operación automática.
- **No devuelve datos enmascarados a los sistemas.** El enmascarado aplica
  al rol Tester de la interfaz web, no a los tokens de sistema.
- **No notifica cambios.** Todavía no hay webhooks: para enterarte de lo que
  cambió, consulta `/personas?actualizadas_desde=`.

---

## 8. Pruebas

La cobertura de esta API vive en `tests/Feature/ApiPadronTest.php`: 21
pruebas que cubren autenticación, habilidades, los nueve endpoints, la
idempotencia del alta y de los eventos, y las cuatro rutas de coincidencia
de `/resolver`.

```bash
php artisan test --filter=ApiPadronTest
```

Si cambias el comportamiento de `/resolver`, ahí es donde se nota.
