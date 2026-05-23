#!/bin/bash
# healthcheck.sh — verifica los 3 contenedores del EVA-IT cada 5 minutos
# Crontab: */5 * * * * /opt/moodle-lms/healthcheck.sh

LOG=/var/log/eva_healthcheck.log
DATE=$(date '+%Y-%m-%d %H:%M:%S')
RESTART_COUNT=0

# ── Función: verificar contenedor ────────────────────────────────────────
check() {
    local name=$1
    local url=$2

    # ¿Está corriendo?
    if ! docker ps --filter "name=^${name}$" --filter "status=running" \
            --format "{{.Names}}" | grep -q "^${name}$"; then
        echo "[$DATE] ✗ $name CAIDO — intentando reiniciar..." >> "$LOG"
        docker start "$name" >> "$LOG" 2>&1
        RESTART_COUNT=$((RESTART_COUNT + 1))
        return
    fi

    # ¿Responde HTTP?
    if [ -n "$url" ]; then
        HTTP=$(curl -sk -o /dev/null -w "%{http_code}" --max-time 5 "$url")
        if [[ "$HTTP" == "200" || "$HTTP" == "301" || "$HTTP" == "302" ]]; then
            echo "[$DATE] ✓ $name OK (HTTP $HTTP)" >> "$LOG"
        else
            echo "[$DATE] ⚠ $name CORRIENDO pero HTTP responde $HTTP — $url" >> "$LOG"
        fi
    else
        echo "[$DATE] ✓ $name OK" >> "$LOG"
    fi
}

# ── Checks ────────────────────────────────────────────────────────────────
check "moodle-app"      "http://localhost/"
check "moodle-db"       ""
check "moodle-terminal" "http://localhost:7681/"

# ── Resumen ───────────────────────────────────────────────────────────────
if [ "$RESTART_COUNT" -gt 0 ]; then
    echo "[$DATE] ⚡ Se reiniciaron $RESTART_COUNT contenedor(es)" >> "$LOG"
fi

# ── Rotar log (máx 500 líneas) ────────────────────────────────────────────
LINES=$(wc -l < "$LOG" 2>/dev/null || echo 0)
if [ "$LINES" -gt 500 ]; then
    tail -200 "$LOG" > "${LOG}.tmp" && mv "${LOG}.tmp" "$LOG"
fi
