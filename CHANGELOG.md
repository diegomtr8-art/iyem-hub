# Registro de cambios

Todos los cambios relevantes de este proyecto se documentan aquí.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado sigue [SemVer](https://semver.org/lang/es/).

## [Sin publicar]

### Agregado

- Catálogo de 13 módulos en `config/modulos.php` con estado, categoría,
  responsable, endpoint de salud, color y orden.
- Servicio `CatalogoModulos`: fuente única de interpretación del catálogo
  para el dashboard, el sidebar y la API.
- Paleta extendida: `iyem.dorado` para KPIs y gráficas, más los semánticos
  `iyem.exito`, `iyem.alerta` e `iyem.error`.
- Rol `Tester` de solo lectura, con `TesterSeeder` y una cuenta que caduca
  a los 90 días.
- `PadronDemoSeeder`: 200 personas ficticias con apellidos yucatecos, CURP
  sintética de entidad inexistente (`ZZ`) y registros en los seis módulos,
  sesgados para reproducir el embudo del emprendedor.
- Columna `users.expira_at` y middleware `VerificaVigencia`, que cierra la
  sesión de las cuentas caducadas.
- Columna `personas.demo` y scope global de aislamiento: una sesión de
  Tester solo alcanza personas de demostración, en la web y en la API.
- Enmascarado de campos sensibles en el modelo `Persona` mediante accessors,
  de modo que también protege `toArray()`, la API y las exportaciones.
- Middleware `RestringeTester`, que le cierra `/user/api-tokens` al rol de
  pruebas.
- Banner permanente de modo de pruebas en `AppLayout`.
- Cinco permisos de acción sobre el padrón: crear, editar, exportar,
  importar y fusionar.
- Iconos nuevos en `IconoModulo` e `IconoNav`, ambos con `aria-label`.

## [0.1.0] - 2026-08-24

Primera versión con control de versiones. Marca el punto de partida del
rediseño de iyemyucatan.com como hub central del ecosistema IYEM.

### Agregado

- Control de versiones con `git`, ramas `main` y `develop`.
- `README.md` con instalación local paso a paso y acceso del usuario de pruebas.
- `CONTRIBUTING.md` con flujo de ramas, convención de commits y checklist de PR.
- `CHANGELOG.md` siguiendo Keep a Changelog.
- `pint.json` con el preset `laravel` para formateo automático de PHP.
- Plantilla de Pull Request en `.github/pull_request_template.md`.
- Integración continua en `.github/workflows/ci.yml`: instala dependencias,
  compila assets, corre la suite de pruebas y verifica el formato con Pint.

### Cambiado

- `.gitignore` reescrito y agrupado por categoría; ahora ignora `.env.*`
  conservando `.env.example`.

### Estado heredado

Lo que ya existía antes de esta versión y sobre lo que se construye:

- Laravel 12 + Jetstream 5.5 + Inertia 2 + Vue 3 + Tailwind 3 + Sanctum.
- Autorización con `spatie/laravel-permission`: 4 roles y permisos `ver-{slug}`.
- Tabla `personas` (33 columnas) y seis tablas de vínculo por módulo.
- Dashboard con cuadrícula de módulos y registro de accesos.
- Padrón con listado, alta y mapa.

[Sin publicar]: https://github.com/iyem/iyem-hub/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/iyem/iyem-hub/releases/tag/v0.1.0
