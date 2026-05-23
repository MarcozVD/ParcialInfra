#!/bin/bash
set -e

MOODLE_DIR=/var/www/html
MOODLEDATA_DIR=/var/moodledata
CONFIG_FILE="${MOODLEDATA_DIR}/config.php"

echo "==> Esperando que PostgreSQL este listo..."
until pg_isready -h "${MOODLE_DB_HOST:-moodle-db}" -p "${MOODLE_DB_PORT:-5432}" -U "${MOODLE_DB_USER}" -q; do
    echo "    PostgreSQL no disponible aun, reintentando en 5s..."
    sleep 5
done
echo "==> PostgreSQL listo."

if [ ! -f "${CONFIG_FILE}" ]; then
    echo "==> Primera ejecucion: instalando Moodle via CLI..."
    php "${MOODLE_DIR}/admin/cli/install.php" \
        --dbtype=pgsql \
        --dbhost="${MOODLE_DB_HOST:-moodle-db}" \
        --dbport="${MOODLE_DB_PORT:-5432}" \
        --dbname="${MOODLE_DB_NAME}" \
        --dbuser="${MOODLE_DB_USER}" \
        --dbpass="${MOODLE_DB_PASS}" \
        --dataroot="${MOODLEDATA_DIR}" \
        --wwwroot="${MOODLE_WWWROOT:-http://192.168.56.104}" \
        --adminuser="admin" \
        --adminpass="${MOODLE_ADMIN_PASS}" \
        --adminemail="${MOODLE_ADMIN_EMAIL:-admin@infraestructura.edu}" \
        --fullname="${MOODLE_SITE_NAME:-EVA Infraestructura Tecnologica}" \
        --shortname="EVA-IT" \
        --lang=es \
        --agree-license \
        --non-interactive

    # Guardar copia en moodledata (persistente) y dejar el original en su lugar
    cp "${MOODLE_DIR}/config.php" "${CONFIG_FILE}"
    chown www-data:www-data "${CONFIG_FILE}"
    chown www-data:www-data "${MOODLE_DIR}/config.php"
    echo "==> Moodle instalado correctamente."
fi

# En reinicios, restaurar config.php como archivo real (nunca symlink)
# -L detecta symlinks: si es symlink o no existe, reemplazar con copia real
if { [ -L "${MOODLE_DIR}/config.php" ] || [ ! -f "${MOODLE_DIR}/config.php" ]; } && [ -f "${CONFIG_FILE}" ]; then
    rm -f "${MOODLE_DIR}/config.php"
    cp "${CONFIG_FILE}" "${MOODLE_DIR}/config.php"
    chown www-data:www-data "${MOODLE_DIR}/config.php"
fi

echo "* * * * * www-data /usr/local/bin/php ${MOODLE_DIR}/admin/cli/cron.php >/dev/null 2>&1" > /etc/cron.d/moodle
chmod 0644 /etc/cron.d/moodle
service cron start

# Actualizar wwwroot si cambió el env var (permite pasar de http a https sin reinstalar)
if [ -f "${MOODLE_DIR}/config.php" ] && [ -n "${MOODLE_WWWROOT}" ]; then
    sed -i "s|\(\\\$CFG->wwwroot\s*=\s*\)'[^']*'|\1'${MOODLE_WWWROOT}'|" "${MOODLE_DIR}/config.php"
    [ -f "${CONFIG_FILE}" ] && \
        sed -i "s|\(\\\$CFG->wwwroot\s*=\s*\)'[^']*'|\1'${MOODLE_WWWROOT}'|" "${CONFIG_FILE}"
    # sslproxy: necesario cuando Nginx hace la terminación SSL
    if [[ "${MOODLE_WWWROOT}" == https://* ]]; then
        grep -q "sslproxy" "${MOODLE_DIR}/config.php" || \
            sed -i "/\\\$CFG->wwwroot/a \\\$CFG->sslproxy = true;" "${MOODLE_DIR}/config.php"
    fi
    echo "==> wwwroot actualizado a: ${MOODLE_WWWROOT}"
fi

echo "==> Iniciando Apache..."
exec "$@"
