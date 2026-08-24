#!/bin/bash
# ============================================================================
#  Despliegue del IYEM Hub a producción (Hostinger)
# ============================================================================
#
#  Uso:
#      bash deploy/desplegar.sh
#
#  Los valores por omisión apuntan a la cuenta del IYEM en Hostinger; se
#  pueden sobrescribir por entorno para desplegar a otro lado.
#
#  AUTENTICACIÓN: con la clave ~/.ssh/id_ed25519_iyemyucatan, nunca con
#  contraseña. Aquí no hay ninguna escrita ni debe haberla: la de esta cuenta
#  ya anda en texto plano en nueve archivos sueltos de htdocs, y ese patrón
#  no se replica. Sin la clave, el script se detiene antes de conectarse.
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

: "${IYEM_SSH_HOST:=195.35.38.222}"
: "${IYEM_SSH_PORT:=65002}"
: "${IYEM_SSH_USER:=u489236361}"
: "${IYEM_SSH_KEY:=$HOME/.ssh/id_ed25519_iyemyucatan}"
: "${IYEM_RUTA:=/home/u489236361/domains/iyemyucatan.com/public_html}"

if [ ! -f "$IYEM_SSH_KEY" ]; then
    echo "x No existe la clave $IYEM_SSH_KEY."
    echo "  El despliegue se autentica con clave, no con contrasena."
    exit 1
fi

LOCAL="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FECHA="$(date +%Y%m%d-%H%M%S)"

SSH_OPTS=(-i "$IYEM_SSH_KEY" -p "$IYEM_SSH_PORT" -o StrictHostKeyChecking=accept-new -o BatchMode=yes)

remoto() {
    ssh "${SSH_OPTS[@]}" "$IYEM_SSH_USER@$IYEM_SSH_HOST" "cd $IYEM_RUTA && $*"
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

# Las credenciales de la base se leen del .env del propio servidor y nunca
# salen de el: el volcado se arma y se comprime alla.
remoto "set -a && . ./.env && set +a && \
    mysqldump --single-transaction --quick --no-tablespaces \
    -u \"\$DB_USERNAME\" -p\"\$DB_PASSWORD\" \"\$DB_DATABASE\" \
    | gzip > ~/respaldo-iyem-$FECHA.sql.gz"

remoto "ls -lh ~/respaldo-iyem-$FECHA.sql.gz"
echo "✓ Respaldo en ~/respaldo-iyem-$FECHA.sql.gz"

# ── 2. Mantenimiento ────────────────────────────────────────────────────────
titulo "2/8  Poniendo el sitio en mantenimiento"
remoto "php artisan down --retry=60 --render='errors::503' || true"

# ── 3. Código ───────────────────────────────────────────────────────────────
titulo "3/8  Subiendo el código"
rsync -avz --delete \
    -e "ssh -i $IYEM_SSH_KEY -p $IYEM_SSH_PORT -o StrictHostKeyChecking=accept-new -o BatchMode=yes" \
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
echo "  ssh -i $IYEM_SSH_KEY -p $IYEM_SSH_PORT $IYEM_SSH_USER@$IYEM_SSH_HOST"
echo "  cd $IYEM_RUTA && php artisan down"
echo "  zcat ~/respaldo-iyem-$FECHA.sql.gz | mysql -u USUARIO -p BASE"
echo "  git checkout v0.1.0  # o el commit anterior"
