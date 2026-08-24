# Registro de cambios

Todos los cambios relevantes de este proyecto se documentan aquí.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado sigue [SemVer](https://semver.org/lang/es/).

## [Sin publicar]

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
