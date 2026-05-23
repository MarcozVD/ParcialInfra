# EVA - Infraestructura Tecnológica

Plataforma LMS Moodle 5.1.4 desplegada con Docker sobre Fedora Server 43.  
Proyecto final del parcial de **Infraestructura Tecnológica**.

## Stack tecnológico

| Componente | Tecnología |
|---|---|
| LMS | Moodle 5.1.4 |
| Reverse proxy / SSL | Nginx (nginx:alpine) |
| Web server | Apache 2.4 (mod_rewrite) |
| Base de datos | PostgreSQL 15 |
| Runtime | PHP 8.2 |
| Terminal web | ttyd 1.7.7 + Ubuntu 22.04 |
| Contenedores | Docker 29.4 + Compose v5.1 |
| Sistema operativo | Fedora Server 43 |

## Arquitectura

```
            Cliente / Navegador
                    │
          ┌─────────┴──────────┐
     HTTP :80              HTTPS :443
          │                    │
    ┌─────▼────────────────────▼─────┐   Fedora Server 43
    │         nginx-proxy            │
    │  HTTP→301→HTTPS · TLS 1.2/1.3  │   moodle-network (Docker bridge)
    │  location /  → moodle:80       │
    │  location /terminal/ → :7681   │
    └───────┬─────────────────┬──────┘
            │                 │ WebSocket
    ┌───────▼──────┐   ┌──────▼──────────┐   ┌───────────────┐
    │  moodle-app  │──►│  moodle-db      │   │moodle-terminal│
    │  Moodle 5.1.4│SQL│  PostgreSQL 15  │   │  ttyd 1.7.7   │
    │  PHP 8.2     │   │  puerto 5432    │   │  puerto 7681  │
    │  Apache 2.4  │   │  Vol: pgdata    │   │               │
    │  Vol: moodle │   └─────────────────┘   └───────────────┘
    │  data        │
    └──────────────┘
    Volúmenes: moodledata / postgresql_data
    Host cron: healthcheck.sh  (cada 5 min)
```

## Funcionalidades implementadas

| Feature | Estado | Descripción |
|---|---|---|
| Moodle 5.1.4 + PostgreSQL 15 | ✅ | LMS completo con BD relacional |
| Nginx reverse proxy | ✅ | Gateway único, puertos 80/443 |
| HTTPS / SSL | ✅ | Certificado auto-firmado, TLS 1.2/1.3, HTTP→301→HTTPS |
| Terminal Linux web | ✅ | ttyd con sesiones efímeras en `/terminal/` |
| 5 unidades de contenido | ✅ | Páginas + foros por unidad |
| Quizzes por unidad | ✅ | 1 quiz × 5 preguntas de opción múltiple por unidad |
| Completion tracking | ✅ | Progreso por actividad (vista/nota) |
| Healthcheck automático | ✅ | Cron cada 5 min, auto-reinicia contenedores caídos |

## Requisitos

- Docker 20+ y Docker Compose v2+
- `openssl` en el host para generar el certificado SSL antes del primer `docker compose up`

## Despliegue

```bash
# Clonar el repositorio
git clone https://github.com/MarcozVD/ParcialInfra.git
cd ParcialInfra

# Generar certificado SSL auto-firmado (solo la primera vez)
mkdir -p nginx/certs
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout nginx/certs/server.key \
  -out nginx/certs/server.crt \
  -subj "/CN=192.168.56.104/O=EVA-IT/C=CO"

# Levantar los 4 contenedores
docker compose up -d --build

# Seguir logs del instalador de Moodle (tarda ~2-3 min la primera vez)
docker logs -f moodle-app
```

Moodle queda disponible en `https://192.168.56.104` una vez que los logs muestren `==> Iniciando Apache...`.

## Quizzes y completion tracking (post-instalación)

```bash
# Crear los 5 quizzes (uno por unidad, 5 preguntas cada uno)
docker cp add_quizzes.php moodle-app:/tmp/
docker exec moodle-app php /tmp/add_quizzes.php

# Activar completion tracking en el curso
docker cp enable_completion.php moodle-app:/tmp/
docker exec moodle-app php /tmp/enable_completion.php
```

## Healthcheck automático

```bash
# Instalar el healthcheck en el crontab del servidor host
sudo cp healthcheck.sh /opt/moodle-lms/healthcheck.sh
sudo chmod +x /opt/moodle-lms/healthcheck.sh
(sudo crontab -l 2>/dev/null; echo "*/5 * * * * /opt/moodle-lms/healthcheck.sh") | sudo crontab -

# Ver el log del healthcheck
sudo tail -f /var/log/eva_healthcheck.log
```

## Credenciales por defecto

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` | `Admin@Infra2024` |
| Docente | `docente01` | `Docente@2024` |
| Estudiante | `estudiante01` | `Estudiante@2024` |
| Estudiante | `estudiante02` | `Estudiante@2024` |
| Estudiante | `estudiante03` | `Estudiante@2024` |

## Estructura del repositorio

```
ParcialInfra/
├── docker-compose.yml        # 4 servicios: nginx-proxy, moodle, moodle-db, terminal
├── Dockerfile                # Imagen Moodle 5.1.4 (php:8.2-apache)
├── Dockerfile.terminal       # Imagen Ubuntu 22.04 + ttyd + herramientas CLI
├── entrypoint.sh             # Instalación automática de Moodle, sslproxy, wwwroot
├── nginx/
│   ├── nginx.conf            # Reverse proxy + SSL + WebSocket proxy
│   └── certs/                # server.crt y server.key (generados localmente)
├── add_quizzes.php           # Crea 5 quizzes con 5 preguntas cada uno
├── enable_completion.php     # Activa completion tracking en todas las actividades
├── healthcheck.sh            # Cron de salud: verifica y reinicia contenedores
└── arquitectura_EVA-IT.svg   # Diagrama de arquitectura
```

## Notas técnicas importantes

- **Moodle 5.x requiere `public/` como DocumentRoot** — el `Dockerfile` configura Apache con `DocumentRoot /var/www/html/public` y el `entrypoint.sh` aplica el ajuste en reinicios.
- **`sslproxy = true` obligatorio detrás de Nginx** — sin esta directiva en `config.php`, Moodle genera bucles de redirección HTTP↔HTTPS cuando Nginx hace la terminación SSL.
- **`config.php` se copia, no se enlaza** — `file_exists()` de PHP retorna `false` para symlinks que cruzan puntos de montaje Docker; el `entrypoint.sh` usa `cp` para persistir la configuración en el volumen.
- **`quiz_slots` en Moodle 5.x** — la columna `questionid` fue eliminada; las preguntas se vinculan mediante `question_bank_entries` → `question_versions` → `question_references`.
- **`completionpassgrade` en Moodle 5.x** — la columna `completiongradeitems` fue renombrada a `completionpassgrade` en `course_modules`.

## Documentación

Ver [Informe_Tecnico_EVA-IT.md](Informe_Tecnico_EVA-IT.md) para la documentación técnica completa del proyecto.
