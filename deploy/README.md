# Despliegue a producción

## Antes de la primera vez

En el servidor, revisa que el `.env` de producción tenga:

```env
APP_ENV=production
APP_DEBUG=false          # obligatorio: con true, cualquier excepción
                         # muestra el .env completo a quien la provoque
APP_URL=https://iyemyucatan.com
```

## Desplegar

```bash
npm run build          # los assets se compilan aquí, no en el servidor
bash deploy/desplegar.sh
```

El guion ya trae los datos de la cuenta del IYEM. Se autentica con la clave
`~/.ssh/id_ed25519_iyemyucatan` y **nunca pide contraseña**: si la clave no
está, se detiene antes de conectarse.

El script se detiene ante cualquier error y deja el sitio en mantenimiento:
es preferible una página de "volvemos pronto" a medio despliegue sirviéndose.

## Qué cambia esta versión en la base

Seis migraciones, **todas aditivas**. Ninguna borra ni renombra nada:

| Migración | Efecto |
|---|---|
| `add_expira_at_to_users_table` | Columna nueva, nullable |
| `add_demo_to_personas_table` | Columna nueva, `default false` |
| `create_sistemas_integrados_table` | Tabla nueva |
| `create_eventos_modulo_table` | Tabla nueva |
| `create_personas_fusiones_table` | Tabla nueva |
| `create_padron_importaciones_table` | Tabla nueva |

Las personas que ya están en el padrón quedan con `demo = false`, que es lo
correcto: son reales.

## Tres cosas que revisar antes de correrlo

**1. `RolePermissionSeeder` reescribe los permisos de los roles.**
Hace `syncPermissions`, así que si alguien ajustó a mano los permisos de un
rol en producción, vuelven a lo que dice el código. Compruébalo antes:

```sql
SELECT r.name AS rol, p.name AS permiso
FROM roles r
JOIN role_has_permissions rp ON rp.role_id = r.id
JOIN permissions p ON p.id = rp.permission_id
ORDER BY r.name, p.name;
```

**2. La cuenta de pruebas.**
`TesterSeeder` crea `tester@iyemyucatan.com` con la contraseña fija
`1234567123`. El script de despliegue **no** la siembra; si la quieres en
producción, córrela aparte y a conciencia:

```bash
php artisan db:seed --class=TesterSeeder --force
```

Está acotada —solo ve personas ficticias, con campos enmascarados, sin
escribir ni exportar, y caduca a los 90 días—, pero es una contraseña
conocida en un sistema con CURP y domicilios reales. La decisión es tuya.

Si la siembras, siembra también el padrón de demostración o no verá nada:

```bash
php artisan db:seed --class=PadronDemoSeeder --force
```

**3. La API sin versionar sigue viva.**
`/api/personas` se conservó como alias deprecado de `/api/v1/personas`.
Cuando confirmes que ningún módulo la llama, quita ese bloque de
`routes/api.php` y borra `tests/Feature/ApiLegadaTest.php`. Para saberlo:

```sql
SELECT name, last_used_at FROM personal_access_tokens ORDER BY last_used_at DESC;
```

## Volver atrás

```bash
ssh -p 65002 usuario@servidor
cd /ruta/public_html
php artisan down
zcat ~/respaldo-iyem-FECHA.sql.gz | mysql -u USUARIO -p BASE
git checkout v0.1.0
composer install --no-dev --optimize-autoloader
php artisan config:cache && php artisan route:cache
php artisan up
```

El respaldo lo genera el propio script como primer paso, antes de tocar
nada. Se guarda en el `home` del usuario con la fecha y hora.
