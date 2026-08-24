#!/bin/bash
# ============================================================================
#  Despliegue del IYEM Hub a producción (Hostinger)
# ============================================================================
#
#  Uso:
#      export IYEM_SSH_HOST="..."      # IP del servidor
#      export IYEM_SSH_PORT="65002"
#      export IYEM_SSH_USER="uXXXXXXXX"
#      export IYEM_RUTA="/home/uXXXXXXXX/domains/iyemyucatan.com/public_html"
#      bash deploy/desplegar.sh
#
#  Las credenciales NO se escriben en este archivo. Se leen del entorno y la
#  contraseña la pide `ssh` por su cuenta (o se usa una llave SSH, que es lo
#  recomendable). Los scripts de este servidor que traen la contraseña en
#  texto plano son un riesgo: cualquiera con acceso al repositorio la tiene.
#
#  Qué hace, en orden:
#      1. Respalda la base de datos            ← lo más importante
#      2. Pone el sitio en mantenimiento
#      3. Sube el código (sin .env, sin storage, sin node_modules)
#      4. composer install --no-dev
#      5. Corre las migraciones
#      6. Siembra roles y permisos
#      7. Cachea configuración, rutas y vistas
#      8. Quita el mantenimiento
#
#  Si algo falla, el script se detiene (`set -e`) y el sitio queda en
#  mantenimiento a propósito: es preferible una página de "volvemos pronto"
#  a medio despliegue sirviéndose a los usuarios.
# ============================================================================

set -euo pipefail

: "${IYEM_SSH_HOST:?Falta IYEM_SSH_HOST}"
: "${IYEM_SSH_PORT:=65002}"
: "${IYEM_SSH_USER:?Falta IYEM_SSH_USER}"
: "${IYEM_RUTA:?Falta IYEM_RUTA (ruta de public_html en el servidor)}"

LOCAL="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FECHA="$(date +%Y%m%d-%H%M%S)"

remoto() {
    ssh -p "$IYEM_SSH_PORT" -o StrictHostKeyChecking=accept-new \
        "$IYEM_SSH_USER@$IYEM_SSH_HOST" "cd $IYEM_RUTA && $*"
}

titulo() {
    echo ""
    echo "──────────────────────────────────────────────────────────────"
    echo "  $1"
    echo "──────────────────────────────────────────────────────────────"
}

# ── 0. Comprobaciones antes de tocar nada ───────────────────────────────────
titulo "0/8  Comprobaciones previas"

if [ -n "$(git -C "$LOCAL" status --porcelain)" ]; then
    echo "✗ Hay cambios sin commitear. Se despliega desde un árbol limpio."
    exit 1
fi

RAMA="$(git -C "$LOCAL" rev-parse --abbrev-ref HEAD)"
if [ "$RAMA" != "main" ]; then
    echo "✗ Estás en «$RAMA». Producción se despliega desde main."
    exit 1
fi

if [ ! -d "$LOCAL/public/build" ]; then
    echo "✗ Falta public/build. Corre «npm run build» antes de desplegar."
    exit 1
fi

echo "✓ Árbol limpio, rama main, assets compilados."
echo "  Commit: $(git -C "$LOCAL" rev-parse --short HEAD) — $(git -C "$LOCAL" log -1 --format=%s)"

# ── 1. Respaldo de la base de datos ─────────────────────────────────────────
titulo "1/8  Respaldando la base de datos"
echo "Este paso es el que permite volver atrás. Si falla, el despliegue se detiene."

remoto "php -r '
    \$env = parse_ini_file(\".env\");
    printf(\"%s|%s|%s\", \$env[\"DB_DATABASE\"], \$env[\"DB_USERNAME\"], \$env[\"DB_PASSWORD\"]);
' > /tmp/iyem_db.txt"

remoto "IFS='|' read -r DB U P < /tmp/iyem_db.txt && \
    mysqldump --single-transaction --quick -u \"\$U\" -p\"\$P\" \"\$DB\" \
    | gzip > ~/respaldo-iyem-$FECHA.sql.gz && rm -f /tmp/iyem_db.txt"

remoto "ls -lh ~/respaldo-iyem-$FECHA.sql.gz"
echo "✓ Respaldo en ~/respaldo-iyem-$FECHA.sql.gz"

# ── 2. Mantenimiento ────────────────────────────────────────────────────────
titulo "2/8  Poniendo el sitio en mantenimiento"
remoto "php artisan down --retry=60 --render='errors::503' || true"

# ── 3. Código ───────────────────────────────────────────────────────────────
titulo "3/8  Subiendo el código"
rsync -avz --delete \
    -e "ssh -p $IYEM_SSH_PORT -o StrictHostKeyChecking=accept-new" \
    --exclude '.git' \
    --exclude '.env' \
    --exclude 'node_modules' \
    --exclude 'vendor' \
    --exclude 'storage/app' \
    --exclude 'storage/logs' \
    --exclude 'storage/framework/sessions' \
    --exclude 'storage/framework/cache' \
    --exclude 'public/storage' \
    --exclude 'tests' \
    "$LOCAL/" "$IYEM_SSH_USER@$IYEM_SSH_HOST:$IYEM_RUTA/"

# ── 4. Dependencias ─────────────────────────────────────────────────────────
titulo "4/8  Instalando dependencias de PHP"
remoto "composer install --no-dev --optimize-autoloader --no-interaction"

# ── 5. Migraciones ──────────────────────────────────────────────────────────
titulo "5/8  Migraciones"
echo "Se agregan: users.expira_at, personas.demo, sistemas_integrados,"
echo "eventos_modulo, personas_fusiones y padron_importaciones."
echo "Todas son aditivas: ninguna borra ni renombra columnas existentes."
remoto "php artisan migrate --force"

# ── 6. Roles y permisos ─────────────────────────────────────────────────────
titulo "6/8  Sembrando roles y permisos"
echo "⚠  RolePermissionSeeder hace syncPermissions sobre los cuatro roles."
echo "   Si en producción alguien ajustó los permisos de un rol a mano,"
echo "   este paso los devuelve a los definidos en el código."
remoto "php artisan db:seed --class=RolePermissionSeeder --force"

# El padrón de demostración NO se siembra: DatabaseSeeder ya lo impide en
# producción, pero además aquí se llama al seeder de roles directamente.

# ── 7. Cachés ───────────────────────────────────────────────────────────────
titulo "7/8  Reconstruyendo cachés"
remoto "php artisan config:clear && php artisan config:cache"
remoto "php artisan route:clear && php artisan route:cache"
remoto "php artisan view:clear && php artisan view:cache"
remoto "php artisan storage:link || true"

# ── 8. Arriba ───────────────────────────────────────────────────────────────
titulo "8/8  Quitando el mantenimiento"
remoto "php artisan up"

# ── Verificación ────────────────────────────────────────────────────────────
titulo "Verificación"
sleep 3
for RUTA_PRUEBA in "/login" "/api/v1/salud" "/manifest.json"; do
    CODIGO="$(curl -s -o /dev/null -w '%{http_code}' "https://iyemyucatan.com$RUTA_PRUEBA")"
    printf "  %-20s HTTP %s\n" "$RUTA_PRUEBA" "$CODIGO"
done

echo ""
echo "Listo. Si algo salió mal:"
echo "  ssh -p $IYEM_SSH_PORT $IYEM_SSH_USER@$IYEM_SSH_HOST"
echo "  cd $IYEM_RUTA && php artisan down"
echo "  zcat ~/respaldo-iyem-$FECHA.sql.gz | mysql -u USUARIO -p BASE"
echo "  git checkout v0.1.0  # o el commit anterior"
