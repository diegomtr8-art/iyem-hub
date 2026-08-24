# Inicio de sesión único (SSO)

Cómo hará cada sistema satélite para que un usuario que ya entró al hub no
tenga que volver a escribir su contraseña.

> **Estado: fase 1 de 2.**
>
> El hub ya emite y canjea tickets. **Ningún módulo satélite lo consume
> todavía.** Este documento es la especificación con la que se integrarán;
> hacerlo exige coordinar seis despliegues y eso se decide aparte.

---

## 1. Por qué tickets y no OAuth

Se evaluó `laravel/passport` y se descartó.

Un servidor OAuth completo resuelve problemas que el IYEM no tiene:
aplicaciones de terceros pidiendo acceso, pantallas de consentimiento,
tokens de refresco de larga vida, revocación granular por aplicación. A
cambio suma un flujo entero que hay que entender y mantener.

Aquí los seis clientes son del propio instituto, corren en su
infraestructura y ya se autentican contra el hub con un token de sistema.
Un ticket firmado, de un solo uso y 60 segundos de vida cubre el caso con
una fracción de la superficie de ataque.

Si algún día el IYEM abre la plataforma a un tercero de verdad, esa será la
señal para reconsiderarlo.

---

## 2. El flujo

```
 Usuario                 Hub                        Módulo satélite
    │                     │                                │
    │  clic en "CREA"     │                                │
    ├────────────────────▶│                                │
    │                     │ ¿tiene ver-crea?               │
    │                     │ genera ticket (60 s, 1 uso)    │
    │                     │                                │
    │   302 a https://crea.iyemyucatan.com/sso?ticket=…    │
    │◀────────────────────┤                                │
    │                                                      │
    ├─────────────────────────────────────────────────────▶│
    │                                                      │
    │                     │  POST /api/v1/sso/validar      │
    │                     │  { ticket }  + token de sistema│
    │                     │◀───────────────────────────────┤
    │                     │                                │
    │                     │  200 { usuario, rol, permisos }│
    │                     ├───────────────────────────────▶│
    │                     │  (el ticket queda consumido)   │
    │                                                      │
    │        sesión iniciada en el módulo                  │
    │◀─────────────────────────────────────────────────────┤
```

### Qué garantiza cada pieza

| Pieza | Contra qué protege |
|---|---|
| **Ticket opaco** | No lleva datos del usuario, solo una referencia. Interceptarlo no revela nada por sí solo. |
| **60 segundos** | Alcanza de sobra para un redireccionamiento; no da margen para reutilizarlo desde un historial o un log de proxy. |
| **Un solo uso** | El canje lo consume. Un segundo intento con el mismo valor falla siempre. |
| **Atado al módulo** | Un ticket emitido para CREA no abre Jurídico, aunque el token del sistema sea válido. |
| **Token de sistema** | Solo un sistema registrado puede canjear. Un ticket robado no sirve sin él. |
| **Se revalida el usuario** | Si la cuenta se desactivó o venció entre la emisión y el canje, el ticket no vale. |

---

## 3. Lo que el hub ya tiene

### `App\Services\SsoService`

```php
// Emite un ticket. Falla si el módulo no existe o el usuario no tiene permiso.
$ticket = app(SsoService::class)->generarTicket($usuario, 'crea');

// Canjea. Devuelve la identidad, o null si el ticket no sirve.
$identidad = app(SsoService::class)->canjearTicket($ticket, $sistema);
```

El ticket se guarda en la caché indexado por su hash SHA-256, no en claro:
quien lea la caché no puede usar lo que encuentre.

### `POST /api/v1/sso/validar`

```bash
curl -X POST https://iyemyucatan.com/api/v1/sso/validar \
  -H "Authorization: Bearer $IYEM_PADRON_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"ticket": "AbC…64 caracteres…xyZ"}'
```

Respuesta (**200**):

```json
{
  "data": {
    "usuario": {
      "id": 12,
      "nombre_completo": "Diego Martínez",
      "email": "diego.martinez@iyemyucatan.com",
      "rol": "Admin Área",
      "permisos": ["ver-crea", "ver-padron", "crear-padron"]
    },
    "modulo": "crea",
    "emitido_at": "2026-08-24T11:42:03-06:00"
  }
}
```

Cuando el ticket no sirve (**401**):

```json
{ "message": "Ticket inválido o vencido." }
```

**El mensaje es el mismo para todos los motivos** —vencido, ya usado, de
otro módulo, usuario dado de baja—. Distinguirlos le daría pistas a quien
esté probando tickets al azar.

El token del sistema necesita la habilidad `sso:validar`:

```bash
php artisan sistemas:registrar crea \
    --habilidades=padron:leer,padron:escribir,eventos:escribir,sso:validar
```

---

## 4. Lo que falta (fase 2)

### En el hub

Que `dashboard.acceder` emita el ticket y redirija con él. Hoy redirige sin
ticket, que es lo correcto mientras ningún módulo sepa qué hacer con uno.

```php
// En DashboardController::acceder, cuando los satélites estén listos:
if ($modulo['externo'] && $modulo['soporta_sso']) {
    $ticket = $this->sso->generarTicket($request->user(), $slug);

    return Inertia::location(
        rtrim($modulo['url_destino'], '/').'/sso?ticket='.$ticket
    );
}
```

Eso pide una clave `soporta_sso` en `config/modulos.php`, para poder
encender el SSO módulo por módulo en vez de los seis a la vez.

### En cada módulo satélite

**1. Configuración**

```env
IYEM_HUB_URL=https://iyemyucatan.com/api/v1
IYEM_HUB_TOKEN=17|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**2. Ruta de entrada**

```php
Route::get('/sso', function (Request $request) {
    $ticket = $request->string('ticket')->toString();

    abort_if(strlen($ticket) !== 64, 400, 'Ticket mal formado.');

    $respuesta = Http::withToken(config('services.hub.token'))
        ->acceptJson()
        ->timeout(5)
        ->post(config('services.hub.url').'/sso/validar', ['ticket' => $ticket]);

    abort_unless($respuesta->successful(), 401, 'No se pudo verificar tu acceso.');

    $identidad = $respuesta->json('data.usuario');

    // La cuenta local se identifica por correo. Se crea si no existe:
    // quien llega con un ticket válido ya fue autenticado por el hub.
    $usuario = User::updateOrCreate(
        ['email' => $identidad['email']],
        [
            'name' => $identidad['nombre_completo'],
            'rol_hub' => $identidad['rol'],
        ]
    );

    Auth::login($usuario, remember: false);
    $request->session()->regenerate();

    return redirect()->intended('/');
})->name('sso.entrada');
```

**3. Tres cosas que no hay que olvidar**

- **`session()->regenerate()`** después del login. Sin eso, un identificador
  de sesión fijado de antemano sobrevive a la autenticación.
- **`timeout(5)`.** Si el hub no contesta, el módulo debe caer en su propio
  formulario de acceso, no quedarse colgado.
- **Conservar el acceso local.** El SSO es una comodidad, no la única
  puerta. Si el hub se cae, el instituto tiene que poder seguir trabajando.

---

## 5. Preguntas que ya salieron

**¿Qué pasa si el usuario cierra sesión en el hub?**
Su sesión en el módulo sigue viva hasta que expire. No hay cierre de sesión
único todavía. Cuando haga falta, la forma más simple es que el hub llame a
un endpoint de cierre en cada módulo donde ese usuario entró, usando la
tabla `accesos` para saber cuáles.

**¿Y si le quitan un permiso?**
El cambio se refleja en el siguiente ticket. La sesión ya abierta en el
módulo conserva los permisos que se le pasaron al entrar. Si un módulo
necesita permisos siempre frescos, que consulte
`GET /api/v1/personas` o defina su propio endpoint contra el hub en cada
petición sensible, en vez de confiar en lo que le llegó al iniciar sesión.

**¿Se puede usar el mismo ticket para dos módulos?**
No. El ticket recuerda para qué módulo se emitió y el canje verifica que
coincida con el slug del sistema que lo presenta.

**¿Por qué 60 segundos y no 5 minutos?**
Un redireccionamiento entre dominios tarda menos de un segundo. Los otros
59 son margen para una red lenta. Cinco minutos serían cinco minutos en los
que un ticket capturado de un log de proxy todavía sirve.

---

## 6. Pendientes antes de encender la fase 2

- [ ] Agregar `soporta_sso` a `config/modulos.php`.
- [ ] Emitir el ticket en `DashboardController::acceder`.
- [ ] Emitir tokens con la habilidad `sso:validar` a los módulos que entren.
- [ ] Implementar `/sso` en el primer módulo (se sugiere CREA) y probarlo en
      un entorno de pruebas antes de tocar producción.
- [ ] Definir qué hace cada módulo cuando el hub no responde.
- [ ] Decidir si hace falta cierre de sesión único.
