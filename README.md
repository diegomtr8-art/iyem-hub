# IYEM Hub — Plataforma central del Instituto Yucateco de Emprendedores

Hub tipo ERP desde el cual el IYEM accede y consulta toda su operación.
Concentra el **Padrón Central** (fuente única de verdad sobre las personas
que atiende el instituto) y funciona como puerta de entrada a los sistemas
satélite del ecosistema.

- **Producción:** https://iyemyucatan.com
- **Stack:** Laravel 12 · Inertia 2 · Vue 3 · Tailwind 3 · Jetstream 5.5 · MySQL
- **Autorización:** `spatie/laravel-permission` (roles + permisos `ver-{slug}`)

---

## Qué resuelve

Cada sistema del IYEM (CREA, Impúlsate, Jurídico, Nódico, Herencia Viva…)
tiene su propia base de datos y su propia idea de quién es "la persona".
El resultado es el mismo emprendedor capturado cinco veces, con cinco
teléfonos distintos y sin forma de responder preguntas como *"¿cuántos de
los que tomaron Impúlsate terminaron pidiendo un crédito CREA?"*.

Este hub no migra esas bases. Les da un `persona_id` común y una API para
resolverlo, de modo que la información se cruce sin mover un solo registro
de su lugar.

---

## Requisitos

| Herramienta | Versión mínima |
|---|---|
| PHP | 8.2 |
| Composer | 2.x |
| Node.js | 20 |
| MySQL | 5.7 (o MariaDB 10.4) |

En Windows, XAMPP con PHP 8.2+ cubre PHP y MySQL.

---

## Instalación local desde cero

```bash
# 1. Clonar dentro del directorio de XAMPP
cd C:\xampp\htdocs
git clone <url-del-repositorio> iyemyucatan
cd iyemyucatan

# 2. Dependencias
composer install
npm install

# 3. Entorno
cp .env.example .env
php artisan key:generate
```

Edita `.env` y ajusta la conexión a MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iyemyucatan
DB_USERNAME=root
DB_PASSWORD=
```

Crea la base de datos vacía (desde phpMyAdmin o por consola):

```sql
CREATE DATABASE iyemyucatan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
# 4. Esquema y datos
php artisan migrate --seed

# 5. Levantar
composer dev        # servidor + colas + logs + Vite, todo junto
```

O por separado, en dos terminales:

```bash
php artisan serve   # http://127.0.0.1:8000
npm run dev
```

---

## Entrar con el usuario de pruebas

El seeder crea una cuenta de solo lectura pensada para prestadores de
servicio social y para demostraciones:

| Campo | Valor |
|---|---|
| Correo | `tester@iyemyucatan.com` |
| Contraseña | `1234567123` |
| Rol | `Tester` |
| Vigencia | 90 días a partir del seed |

Esa cuenta **solo ve personas de demostración** (`demo = true`) y tiene los
campos sensibles enmascarados (CURP, RFC, teléfono y domicilio se muestran
como `****...89`). No puede exportar, no puede escribir en el padrón y no
entra al panel de administración.

El `UserSeeder` además imprime en consola la contraseña generada al azar
para `admin@iyem.mx` (Super Admin). Anótala: no se vuelve a mostrar.

---

## Comandos útiles

```bash
php artisan test                  # suite completa
./vendor/bin/pint                 # formatear código
./vendor/bin/pint --test          # verificar formato sin escribir
php artisan padron:duplicados     # reporte de personas duplicadas
php artisan migrate:fresh --seed  # reconstruir la base (destructivo)
npm run build                     # compilar assets para producción
```

---

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md) | Cómo encaja el hub en el ecosistema y cómo funciona el padrón central |
| [`docs/API_PADRON.md`](docs/API_PADRON.md) | Endpoints, autenticación por sistema y ejemplos de `curl` |
| [`docs/SSO.md`](docs/SSO.md) | Cómo cada sistema satélite consumirá el inicio de sesión único |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | Flujo de ramas, convención de commits y checklist de PR |
| [`CHANGELOG.md`](CHANGELOG.md) | Historial de versiones |
| [`DATABASE_SCHEMA.md`](DATABASE_SCHEMA.md) | Esquema de tablas |

---

## Licencia

Software interno del Instituto Yucateco de Emprendedores. Todos los derechos reservados.
