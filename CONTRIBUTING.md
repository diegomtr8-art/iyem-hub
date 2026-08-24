# Cómo contribuir al IYEM Hub

Este documento es corto a propósito. Si algo aquí te estorba para trabajar,
dilo en el PR en vez de saltártelo en silencio.

---

## Ramas

```
main         Producción (iyemyucatan.com). Protegida.
             Solo recibe merges desde develop, vía Pull Request.

develop      Integración. Es la rama base de todos.
             De aquí sales y aquí regresas.

feature/*    Una rama por tarea.      feature/padron-ficha-360
fix/*        Correcciones.            fix/dashboard-scroll-ipad
docs/*       Documentación.           docs/api-padron
```

Flujo normal:

```bash
git checkout develop
git pull origin develop
git checkout -b feature/lo-que-vas-a-hacer
# ... trabajas, commiteas ...
git push -u origin feature/lo-que-vas-a-hacer
# abres PR contra develop
```

Nunca hagas `push` directo a `main`. Nunca hagas `push --force` a `develop`.

---

## Commits

Formato obligatorio (Conventional Commits, descripción en español,
en imperativo y sin punto final):

```
<tipo>(<alcance>): <descripción>
```

Tipos permitidos:

| Tipo | Cuándo |
|---|---|
| `feat` | Funcionalidad nueva |
| `fix` | Corrección de un defecto |
| `docs` | Solo documentación |
| `style` | Formato, espacios, comas — sin cambio de comportamiento |
| `refactor` | Reorganizar código sin cambiar lo que hace |
| `test` | Agregar o corregir pruebas |
| `chore` | Dependencias, configuración, tareas de mantenimiento |

Ejemplos reales de este repositorio:

```
feat(padron): agregar ficha 360 de la persona
fix(dashboard): corregir scroll horizontal en iPad vertical
docs(api): documentar endpoint de resolver persona
style(login): ajustar contraste del botón primario
refactor(consultas): extraer filtros a un composable
test(api): agregar pruebas del endpoint de búsqueda
chore(deps): actualizar dependencias de npm
```

Un commit debe poder describirse en una línea. Si necesitas la palabra "y"
para explicar qué hace, probablemente son dos commits.

---

## Antes de abrir el PR

```bash
./vendor/bin/pint          # formatea
php artisan test           # todo verde
npm run build              # compila sin errores
```

Si `pint` reescribió archivos, commitea ese cambio aparte como
`style(<alcance>): aplicar formato de pint`.

---

## Checklist de Pull Request

La plantilla en `.github/pull_request_template.md` la trae ya escrita.
Lo mínimo que se revisa:

- [ ] La rama sale de `develop` y apunta a `develop`.
- [ ] Los commits siguen la convención.
- [ ] `php artisan test` pasa en local.
- [ ] `./vendor/bin/pint --test` no reporta nada.
- [ ] **Capturas en iPhone (390×844) y en iPad (820×1180)** si tocaste la interfaz.
- [ ] No hay `dd()`, `dump()`, `var_dump()` ni `console.log` olvidados.
- [ ] No hay credenciales, tokens ni URLs internas escritas a mano en el código.
- [ ] Si agregaste una migración, corre también hacia atrás (`migrate:rollback`).
- [ ] Si tocaste el padrón o la API, hay una prueba que lo cubre.

---

## Convenciones de código

- **Todo en español:** nombres de variables, métodos, rutas, tablas, columnas,
  comentarios y textos de interfaz. Las excepciones son las que impone el
  framework (`up`, `down`, `handle`, `render`, `boot`…).
- **PHP:** `./vendor/bin/pint` manda. Preset `laravel`.
- **Vue:** `<script setup>`, Composition API, componentes en `PascalCase`.
- **Tailwind:** usa los tokens de `tailwind.config.js` (`iyem-*`, `tinta-*`).
  No escribas colores en hexadecimal dentro de las clases.
- **Nada de `dd()` ni `dump()`** en código que se commitea.
- **Ninguna credencial en el código.** Todo por `.env` y `config/`.

---

## Versionado

Seguimos [SemVer](https://semver.org/lang/es/) y
[Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

Al liberar a `main`:

```bash
git checkout main
git merge --no-ff develop
git tag -a v0.2.0 -m "Descripción corta de la versión"
git push origin main --tags
```

Actualiza `CHANGELOG.md` **antes** de etiquetar, moviendo lo que esté en
`[Sin publicar]` a la versión nueva.
