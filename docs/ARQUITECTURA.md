# Arquitectura del IYEM Hub

Cómo encaja este proyecto en el ecosistema del instituto y por qué está
construido así.

---

## 1. El problema

El IYEM opera con sistemas independientes. Cada uno resuelve bien lo suyo y
cada uno tiene su propia base de datos:

| Sistema | Qué hace |
|---|---|
| CREA | Créditos a emprendedores |
| Impúlsate | Capacitación y citas de asesoría |
| Jurídico | Asesorías legales |
| Nódico | Coworking |
| Herencia Viva | Venta de artesanía |
| Asistencia | Control del personal |

El mismo emprendedor aparece en varios, capturado por distinta gente, en
distintos momentos, con distintos criterios. El resultado:

- **Nadie sabe cuántas personas atiende el instituto.** Sumar los padrones
  da un número inflado; no hay forma de saber cuánto.
- **No se pueden responder preguntas que cruzan sistemas.** "¿Cuántos de los
  que tomaron Impúlsate terminaron pidiendo un crédito?" exige exportar dos
  bases y cruzarlas a mano en Excel.
- **Los datos envejecen distinto.** La persona actualiza su teléfono en CREA
  y en Impúlsate sigue el viejo.

---

## 2. La decisión: un padrón central, no una migración

La salida obvia sería juntar todo en una sola base. Se descartó.

Migrar seis sistemas en producción significa detener la operación del
instituto, reescribir seis aplicaciones y aceptar que cualquier error se
lleva por delante trámites en curso. El costo es enorme y el beneficio
—tener los datos juntos— se puede conseguir de otra forma.

**Lo que se hizo:** el hub mantiene un padrón central de personas y expone
una API. Cada sistema satélite conserva su base y agrega una sola columna:

```sql
ALTER TABLE solicitudes ADD COLUMN persona_id BIGINT UNSIGNED NULL;
```

Esa columna es todo el acoplamiento. A cambio, el instituto obtiene una
identidad común y consultas cruzadas.

```
                         ┌──────────────────────────────┐
                         │        IYEM HUB              │
                         │   iyemyucatan.com            │
                         │                              │
                         │  ┌────────────────────────┐  │
                         │  │   Padrón Central       │  │
                         │  │   tabla `personas`     │  │
                         │  └───────────┬────────────┘  │
                         │              │               │
                         │   Dashboard · Consultas 360° │
                         │   Ficha 360° · Buscador ⌘K   │
                         └──────────────┬───────────────┘
                                        │
                              API  /api/v1/personas
                              (Sanctum, token por sistema)
                                        │
        ┌──────────────┬────────────────┼────────────────┬──────────────┐
        │              │                │                │              │
   ┌────┴────┐   ┌─────┴─────┐   ┌──────┴─────┐   ┌──────┴─────┐  ┌─────┴────┐
   │  CREA   │   │ Impúlsate │   │  Jurídico  │   │   Nódico   │  │ H. Viva  │
   │ BD suya │   │  BD suya  │   │  BD suya   │   │  BD suya   │  │ BD suya  │
   │+persona │   │ +persona  │   │ +persona   │   │ +persona   │  │ +persona │
   │   _id   │   │   _id     │   │   _id      │   │   _id      │  │   _id    │
   └─────────┘   └───────────┘   └────────────┘   └────────────┘  └──────────┘
```

---

## 3. La pieza clave: `POST /personas/resolver`

Un padrón central solo sirve si nadie duplica. El endpoint que lo garantiza
recibe lo que el módulo sepa de la persona y devuelve su `persona_id`.

```
El módulo pregunta:  { curp, nombre, teléfono }
                              │
                              ▼
              ┌──────────────────────────────┐
              │  ¿Coincide alguna CURP?      │──── sí ──▶ devuelve ese id
              └──────────────┬───────────────┘
                             │ no
              ┌──────────────▼───────────────┐
              │  ¿Algún RFC?                 │──── sí ──▶ devuelve ese id
              └──────────────┬───────────────┘
                             │ no
              ┌──────────────▼───────────────┐
              │  ¿Algún correo?              │──── sí ──▶ devuelve ese id
              └──────────────┬───────────────┘
                             │ no
              ┌──────────────▼───────────────┐
              │  ¿Teléfono Y nombre ≥ 82 %?  │──── sí ──▶ devuelve ese id
              └──────────────┬───────────────┘
                             │ no
                             ▼
                    crea la persona y devuelve el id nuevo
```

**Por qué el teléfono no basta solo.** En Yucatán es común que varios
miembros de un negocio familiar den el mismo número. Emparejar por teléfono
a secas juntaría a una madre y a su hijo bajo un mismo expediente. Por eso
el cuarto criterio exige además que los nombres se parezcan al menos 82 %,
comparados sin acentos y sin importar el orden de las palabras.

**Qué pasa con los datos que manda el módulo.** Se completan los campos
vacíos y **no se pisa ninguno que ya tenga valor**. Si CREA dice que el
teléfono es otro, ese conflicto lo resuelve una persona desde la ficha, no
una llamada a la API.

Detalles y ejemplos: [`API_PADRON.md`](API_PADRON.md).

---

## 4. Estructura del proyecto

```
app/
├── Console/Commands/
│   ├── DetectarDuplicados.php     padron:duplicados
│   └── RegistrarSistema.php       sistemas:registrar
├── Exports/
│   ├── ConsultaExport.php         XLSX de una consulta
│   └── PadronExport.php           XLSX del padrón
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/                API del padrón
│   │   └── ...                    pantallas del hub
│   └── Middleware/
│       ├── CabecerasDeSeguridad.php
│       ├── RestringeTester.php
│       └── VerificaVigencia.php
├── Models/
└── Services/                      ← la lógica de negocio vive aquí
    ├── CatalogoModulos.php        interpreta config/modulos.php
    ├── Consultas/                 las seis consultas 360°
    ├── DetectorDuplicados.php
    ├── FichaPersona.php
    ├── FusionadorPersonas.php
    ├── ImportadorPadron.php
    ├── IndicadoresHub.php
    ├── ResolvedorPersonas.php     ← la pieza que evita duplicados
    ├── SaludModulos.php
    └── SsoService.php
```

**Los controladores no deciden nada.** Validan, delegan en un servicio y
arman la respuesta. Todo lo que un módulo satélite podría necesitar algún
día vive en `app/Services`, para poder llamarlo desde una ruta web, desde
la API o desde un comando sin duplicar la regla.

---

## 5. Modelo de datos

### El centro

```
personas                   La persona. 34 columnas: identidad, domicilio,
                           demográficos, presencia digital, gestión.
├── personas_etiquetas     Etiquetas libres (emprendedor, artesano, vip…)
├── personas_auditorias    Cada cambio: campo, antes, después, quién, qué módulo
└── personas_fusiones      Bitácora de fusiones, con lo necesario para deshacerlas
```

### Tablas de vínculo por módulo

Un reflejo local de lo que cada sistema satélite guarda de esa persona.
Todas apuntan a `personas.id`:

```
crea_solicitudes · impulsate_inscripciones · nodico_membresias
herencia_viva_clientes · juridico_asesorias · citas_agendamientos
```

Son las que hacen posibles las consultas cruzadas sin salir a preguntarle a
seis servidores en tiempo real.

### Operación del hub

```
sistemas_integrados    Sistemas satélite con token de API
eventos_modulo         Hechos que los módulos reportan (POST /eventos)
accesos                Quién entró a qué módulo y cuándo
padron_importaciones   Bitácora de cargas masivas
users                  Cuentas del hub (+ expira_at)
roles / permissions    spatie/laravel-permission
```

---

## 6. Autorización

Dos sistemas de permisos que no se mezclan.

### Personas → roles y permisos

`spatie/laravel-permission`. Cinco roles:

| Rol | Alcance |
|---|---|
| Super Admin | Todo, incluido el panel y la fusión de duplicados |
| Admin Área | Módulos de su área, escribe e importa en el padrón |
| Supervisor | Consulta y exporta; no escribe |
| Operario | Un módulo y consulta del padrón |
| Tester | Ve los 13 módulos, solo datos ficticios y enmascarados |

Cada módulo de `config/modulos.php` genera su permiso `ver-{slug}`. Agregar
un módulo al catálogo genera su permiso al correr el seeder.

### Sistemas → tokens con habilidades

Los satélites no usan la cuenta de un empleado. Cada uno tiene su registro
en `sistemas_integrados` y su token de Sanctum con habilidades
(`padron:leer`, `padron:escribir`, `eventos:escribir`, `sso:validar`).

**Por qué separados.** Si la integración colgara de la cuenta de quien la
configuró, el día que esa persona deje el instituto y se desactive su
cuenta, CREA dejaría de resolver personas. La integración es del sistema,
no de un empleado.

---

## 7. El rol Tester

Sirve para enseñar la plataforma completa —a prestadores de servicio social,
a otras áreas, a proveedores— sin exponer un solo dato real. Tres capas:

1. **Aislamiento.** Un scope global en `Persona` restringe el padrón a
   `demo = true` mientras haya sesión de Tester. Va como scope global y no
   como filtro en cada consulta para que aplique también en la API, en el
   mapa, en el buscador y en las consultas cruzadas, sin depender de que
   cada consulta nueva se acuerde de filtrarlo.

2. **Enmascarado.** CURP, RFC, clave de elector, teléfonos y domicilio se
   muestran como `********89`. Se implementa con un accessor por campo en el
   modelo, no en la vista: así protege también `toArray()`, y con eso la
   respuesta de Inertia, la API y las exportaciones.

3. **Agregados.** Las tablas de módulo no tienen columna `demo`, así que
   `IndicadoresHub` acota sus conteos a las personas visibles cuando la
   sesión es de Tester. Sin eso se filtraría el número real de solicitudes
   de crédito, que es un dato real aunque no lleve nombre.

La cuenta caduca a los 90 días (`users.expira_at`); el middleware
`VerificaVigencia` cierra la sesión cuando esa fecha pasa.

---

## 8. Decisiones que vale la pena conocer

### El semáforo de módulos se consulta después de pintar la página

Sondear diez subdominios durante el render dejaría el dashboard en blanco
varios segundos cada vez que uno no contestara. El navegador pide
`/dashboard/salud` cuando la página ya está a la vista; `Http::pool` lanza
los sondeos en paralelo con 3 s de tope y el resultado se cachea 5 minutos.

Los estados son cuatro y no dos: mientras el sondeo viaja, el punto queda
gris pulsante; un módulo sin endpoint de salud se marca "sin monitoreo".
Pintarlo verde por omisión sería afirmar algo que el hub no sabe.

### Los módulos en desarrollo se muestran pero no se enlazan

`CatalogoModulos` marca como navegables solo los estados `produccion` y
`beta`. Un módulo en `desarrollo` aparece en la cuadrícula con su badge,
pero no lleva a ninguna parte: mandar a alguien a una URL que todavía no
existe es peor que decirle que aún no está lista.

### El agrupamiento del mapa es por municipio, no geométrico

Se descartó `leaflet.markercluster`. El padrón se reparte entre municipios y
esa es la unidad con la que el instituto planea sus brigadas; agrupar por
cercanía geométrica daría racimos que no corresponden a ninguna decisión
operativa. Además ahorra la dependencia.

### El service worker no cachea HTML ni la API

Solo assets con huella de contenido (`/build/...`) e iconos. En una
plataforma con datos personales y varios roles, servir una página guardada
podría enseñarle a un usuario lo que vio el anterior, o revivir una sesión
ya cerrada.

### Las consultas son cerradas, no un constructor libre

El instituto tiene seis preguntas concretas que hoy no puede responder.
Resolverlas bien vale más que un generador genérico que nadie sabría usar.
Cuando aparezca la séptima, se agrega una clase a `app/Services/Consultas`
y se registra; el controlador y la interfaz no se tocan.

---

## 9. Límites conocidos

Dicho de frente:

- **Las tablas de módulo son un reflejo, no la fuente.** Hoy se llenan por
  seeder y por `POST /eventos`. Mientras los satélites no reporten, las
  consultas cruzadas solo ven lo que el hub alcanzó a saber.
- **CURP y correo tienen índice UNIQUE**, así que esos duplicados no pueden
  existir en esta base. El detector conserva los criterios —una carga
  histórica o una migración futura los volvería posibles— y la pantalla lo
  advierte, para que "cero duplicados por CURP" no se lea como calidad.
- **El catálogo de municipios no cubre los 106 del estado.** `config/
  municipios_yucatan.php` tiene los principales. La consulta de cobertura
  territorial lo declara en pantalla.
- **El SSO está a medias.** El hub emite y canjea tickets; ningún satélite
  los consume todavía. Ver [`SSO.md`](SSO.md).
- **No hay webhooks.** Para enterarse de lo que cambió hay que consultar
  `/personas?actualizadas_desde=`.
- **Los responsables de módulo en `config/modulos.php` son marcadores.**
  Falta confirmarlos contra el organigrama real del instituto.

---

## 10. Documentos relacionados

| Documento | Contenido |
|---|---|
| [`API_PADRON.md`](API_PADRON.md) | Endpoints, autenticación y guía de integración |
| [`SSO.md`](SSO.md) | Inicio de sesión único entre el hub y los módulos |
| [`../DATABASE_SCHEMA.md`](../DATABASE_SCHEMA.md) | Esquema de tablas |
| [`../CONTRIBUTING.md`](../CONTRIBUTING.md) | Flujo de trabajo del equipo |
