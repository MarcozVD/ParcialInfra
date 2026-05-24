# INFORME TÉCNICO
## Plataforma EVA-IT — Entorno Virtual de Aprendizaje para Infraestructura Tecnológica
### Parcial de Infraestructura Tecnológica

---

**Institución:** [Nombre de la institución]  
**Curso:** Infraestructura Tecnológica  
**Equipo:** [Nombres del equipo]  
**Fecha:** Mayo 2026  
**Versión:** 2.0  

---

## TABLA DE CONTENIDOS

1. Portada
2. Introducción
3. Objetivo General y Objetivos Específicos
4. Descripción de la Arquitectura Propuesta
5. Tecnologías Utilizadas
6. Justificación de las Decisiones Técnicas
7. Proceso de Instalación y Configuración
8. Configuración de Moodle
9. Pruebas Realizadas
10. Evidencias del Funcionamiento de la Plataforma
11. Análisis de Seguridad Básica
12. Plan de Respaldo y Mantenimiento
13. Matriz Comparativa de la Solución Implementada
14. Conclusiones
15. Referencias

---

## 1. PORTADA

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                   │
│            INFRAESTRUCTURA TECNOLÓGICA — PARCIAL                 │
│                                                                   │
│         Implementación de Plataforma LMS con Docker              │
│              EVA-IT: Entorno Virtual de Aprendizaje              │
│                                                                   │
│  Stack:  Moodle 5.1.4 · PostgreSQL 15 · Nginx · Docker · ttyd     │
│  Servidor: Fedora Server 43 · VirtualBox · 192.168.56.104        │
│                                                                   │
│  [Nombres del equipo]                                            │
│  [Fecha de entrega]                                              │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

> 📷 **[CAPTURA 1]** Foto grupal del equipo o portada institucional

---

## 2. INTRODUCCIÓN

La educación en infraestructura tecnológica enfrenta un desafío particular: los estudiantes necesitan no solo comprender conceptos teóricos, sino practicar comandos y configuraciones reales sobre servidores Linux. Las plataformas de aprendizaje tradicionales ofrecen contenido estático — videos, PDFs, foros — pero no un entorno donde el alumno pueda ejecutar comandos mientras estudia.

Este proyecto implementa **EVA-IT** (*Entorno Virtual de Aprendizaje para Infraestructura Tecnológica*), una plataforma LMS construida sobre **Moodle 5.1.4** desplegada mediante **Docker Compose** sobre un servidor **Fedora Server 43**. Su característica diferenciadora es la integración de una **terminal Linux interactiva embebida en el propio EVA**, accesible desde el navegador sin instalación adicional, que permite al estudiante ejecutar en tiempo real los mismos comandos enseñados en cada unidad del curso.

La plataforma cubre cinco unidades temáticas:
- **Unidad 1:** Servidores Linux (comandos, usuarios, procesos, servicios)
- **Unidad 2:** Servicios Web (Apache, Nginx, PHP, SSL/TLS)
- **Unidad 3:** Bases de Datos (PostgreSQL, MySQL, backups)
- **Unidad 4:** Docker y contenedores
- **Unidad 5:** Seguridad y respaldo (firewall, fail2ban, iptables, SSH)

---

## 3. OBJETIVO GENERAL Y OBJETIVOS ESPECÍFICOS

### Objetivo General

Diseñar, implementar y desplegar una plataforma LMS de alta disponibilidad utilizando tecnologías de contenedorización (Docker), que permita a los estudiantes de Infraestructura Tecnológica acceder a contenido estructurado por unidades y practicar comandos Linux desde el navegador, sin requerir infraestructura adicional en el equipo del alumno.

### Objetivos Específicos

1. **Desplegar Moodle 5.1.4** sobre Docker con arquitectura de microservicios, separando la aplicación, la base de datos y el entorno de práctica en contenedores independientes.

2. **Configurar PostgreSQL 15** como motor de base de datos con autenticación segura, healthchecks automatizados y volúmenes persistentes para garantizar la integridad de los datos del curso.

3. **Estructurar el contenido del curso** en cinco unidades temáticas, cada una con al menos tres páginas de contenido técnico y un foro de discusión, cubriendo los temas del syllabus de Infraestructura Tecnológica.

4. **Integrar una terminal Linux interactiva** (ttyd) accesible desde el navegador, que soporte los comandos de todas las unidades del curso, con sesiones efímeras que se reinician al cerrar el navegador para garantizar un entorno limpio por sesión.

5. **Implementar medidas de seguridad básica** incluyendo HTTPS, cortafuegos (UFW/iptables), protección contra ataques de fuerza bruta (fail2ban) y gestión de contraseñas seguras para todos los usuarios del sistema.

6. **Documentar** el proceso completo de instalación, configuración y operación de la plataforma, incluyendo un plan de respaldo y mantenimiento para garantizar la continuidad del servicio.

---

## 4. DESCRIPCIÓN DE LA ARQUITECTURA PROPUESTA

### 4.1 Diagrama General

```
                         INTERNET / RED LOCAL
                               │
                    ┌──────────▼──────────┐
                    │   Fedora Server 43  │
                    │   192.168.56.104    │
                    │   VirtualBox VM     │
                    └──────────┬──────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
             ┌───────────────────────────────┐
             │          nginx-proxy          │
             │  nginx:alpine  ·  :80 / :443  │
             │  HTTP → 301 → HTTPS           │
             │  SSL: TLS 1.2/1.3  (OpenSSL)  │
             │  / → moodle:80                │
             │  /terminal/ → terminal:7681   │
             └────┬──────────────────┬───────┘
                  │ proxy_pass       │ WebSocket
    ┌─────────────▼──┐  ┌────────────▼──┐  ┌──────────────────┐
    │  moodle-app    │  │  moodle-db    │  │ moodle-terminal  │
    │  Solo interno  │  │  Puerto: 5432 │  │  Puerto: 7681    │
    │  Moodle 5.1.4  │  │ PostgreSQL 15 │  │  ttyd + Ubuntu   │
    │  PHP 8.2       │  │               │  │  Bash + tools    │
    │  Apache 2.4    │  │  Vol: pgdata  │  │                  │
    │  Vol: moodle-  │  │               │  │  /docker.sock    │
    │  data          │  │               │  │  (montado)       │
    │  5 quizzes     │  │               │  │                  │
    │  completion ✓  │  │               │  │                  │
    └────────────────┘  └───────────────┘  └──────────────────┘
              └─────────────────┼───────────────┘
                     moodle-network (Docker bridge)

    Host: healthcheck.sh (cron */5 * * * *)
          Verifica y reinicia contenedores caídos
```

### 4.2 Componentes del Sistema

| Componente | Tecnología | Puerto | Función |
|---|---|---|---|
| **Reverse proxy / SSL** | Nginx (nginx:alpine) | 80 / 443 | Gateway único, HTTP→HTTPS, WebSocket proxy |
| **LMS** | Moodle 5.1.4 + PHP 8.2 + Apache 2.4 | 80 (interno) | Plataforma de aprendizaje |
| **Base de datos** | PostgreSQL 15 | 5432 (interno) | Persistencia de datos del curso |
| **Terminal web** | ttyd 1.7.7 + Ubuntu 22.04 | 7681 | Terminal interactiva para práctica |
| **Orquestador** | Docker Compose | — | Gestión del ciclo de vida |
| **Healthcheck** | Bash + cron (host) | — | Monitoreo y auto-recuperación cada 5 min |
| **Host** | Fedora Server 43 | — | Sistema operativo del servidor |

### 4.3 Flujo de Datos

```
Alumno (Navegador)
       │
       ├──► http://192.168.56.104:80   ──► [nginx-proxy] ──► 301 ──► https://
       │                                                                │
       └──► https://192.168.56.104:443 ──► [nginx-proxy]
                                                  │
                    ┌─────────────────────────────┤
                    │                             │  WebSocket
            location /                   location /terminal/
                    │                             │
             [moodle-app]               [moodle-terminal]
              PHP 8.2 / Apache          ttyd + bash
                    │
              [moodle-db]
              PostgreSQL 15
```

### 4.4 Persistencia y Volúmenes

```
postgresql_data  ──►  /var/lib/postgresql/data   (datos BD)
moodledata       ──►  /var/moodledata             (archivos Moodle)
/var/run/docker.sock  ──► /var/run/docker.sock    (Docker CLI en terminal)
```

> 📷 **[CAPTURA 2]** Resultado de `docker ps` mostrando los 3 contenedores activos

> 📷 **[CAPTURA 3]** Resultado de `docker network inspect moodle-lms_moodle-network`

---

## 5. TECNOLOGÍAS UTILIZADAS

### 5.1 Tabla de Tecnologías

| Capa | Tecnología | Versión | Rol |
|---|---|---|---|
| **Sistema operativo** | Fedora Server | 43 | Host del servidor |
| **Virtualización** | Oracle VirtualBox | 7.x | Entorno de VM |
| **Contenedorización** | Docker Engine | 27.x | Runtime de contenedores |
| **Orquestación** | Docker Compose | v2 plugin | Gestión multi-contenedor |
| **Reverse proxy** | Nginx | 1.31 (alpine) | Gateway HTTPS, WebSocket proxy |
| **Protocolo** | SSL/TLS | 1.2 / 1.3 | Cifrado extremo a extremo |
| **Certificados** | OpenSSL | — | Certificado X.509 auto-firmado 365 días |
| **LMS** | Moodle | 5.1.4 | Plataforma educativa |
| **Lenguaje** | PHP | 8.2 | Backend Moodle |
| **Web Server** | Apache HTTP Server | 2.4 | Servidor web Moodle (interno) |
| **Base de datos** | PostgreSQL | 15 | Motor de BD relacional |
| **Terminal web** | ttyd | 1.7.7 | Terminal vía WebSocket |
| **OS Terminal** | Ubuntu | 22.04 LTS | Base del contenedor terminal |
| **Control versiones** | Git + GitHub | — | Versionado del proyecto |
| **Firewall** | UFW + iptables | — | Control de acceso de red |
| **Anti-fuerza bruta** | fail2ban | — | Protección SSH/servicios |
| **Healthcheck** | Bash + cron | — | Monitoreo automático cada 5 minutos |

### 5.2 Herramientas del Contenedor Terminal

El contenedor `moodle-terminal` incluye todas las herramientas enseñadas en el curso:

```
Unidad 1 - Linux:    vim, nano, htop, ps, top, df, free, ip, ss,
                     useradd, chmod, chown, find, locate, tree

Unidad 2 - Web:      apache2, nginx, php8.1-cli, php8.1-fpm, openssl,
                     curl, wget

Unidad 3 - BD:       postgresql-client (psql, pg_dump), mysql-client

Unidad 4 - Docker:   docker-ce-cli, docker-compose-plugin

Unidad 5 - Seguridad: iptables, ufw, fail2ban, tcpdump, iftop,
                      openssh-client, ssh-keygen
```

---

## 6. JUSTIFICACIÓN DE LAS DECISIONES TÉCNICAS

### 6.1 Moodle 5.1.4 sobre Docker vs. Instalación Nativa

Se eligió **Docker** para desplegar Moodle en lugar de una instalación nativa por las siguientes razones:

| Criterio | Docker | Instalación nativa |
|---|---|---|
| **Portabilidad** | ✅ La imagen corre en cualquier host | ❌ Dependiente del SO |
| **Reproducibilidad** | ✅ `docker compose up` recrea el entorno exacto | ❌ Pasos manuales propensos a error |
| **Aislamiento** | ✅ Cada componente en su propio contenedor | ❌ Conflictos de dependencias |
| **Rollback** | ✅ Cambiar a imagen anterior con una línea | ❌ Proceso complejo |
| **Escalabilidad** | ✅ Escalar servicios individualmente | ❌ Monolítico |

### 6.2 PostgreSQL vs. MySQL/MariaDB

Moodle soporta ambos motores. Se eligió **PostgreSQL 15** por:
- Mayor robustez en integridad referencial y transacciones ACID
- Mejor rendimiento en consultas analíticas complejas (informes Moodle)
- Compatibilidad nativa con la versión 5.x de Moodle
- La imagen oficial `postgres:15` es más ligera que `mysql:8`

### 6.3 ttyd como Terminal Web Interactiva

Se descartaron alternativas como:
- **Wetty**: requiere Node.js y SSH, más pesado
- **Shellinabox**: no mantenido activamente desde 2017
- **Xterm.js directo**: requiere backend personalizado

**ttyd** fue elegido porque:
- Binario único sin dependencias de runtime
- WebSocket nativo (baja latencia)
- `--once` flag: cada sesión es efímera — el contenedor se reinicia con Docker `restart: always`
- Solo 2.8 MB de binario, imagen Docker mínima

### 6.4 Ubuntu 22.04 LTS para el Contenedor Terminal

El servidor real del curso usa **Fedora Server**. Se eligió Ubuntu 22.04 para el contenedor terminal porque:
- Repositorios `apt` más completos para herramientas de práctica (php8.1, postgresql-client, etc.)
- LTS con soporte hasta 2027
- Se implementaron **wrappers** que traducen comandos Fedora → Ubuntu:
  - `dnf install` → `apt-get update && apt-get install`
  - `systemctl` → `service`
  - `journalctl` → `tail /var/log/`
  - `firewall-cmd` → `ufw`
  - `getenforce` → `aa-status` (AppArmor)

Esto permite que el alumno practique los comandos del curso (pensados para Fedora) dentro del contenedor Ubuntu sin confusión.

### 6.5 Sesiones Efímeras en la Terminal

La combinación `ttyd --once` + `restart: always` garantiza:
1. Cada alumno que abre la terminal recibe un entorno **completamente limpio**
2. Al cerrar el navegador, Docker reinicia el contenedor automáticamente
3. Ningún alumno puede ver o afectar el trabajo de otro
4. El contenedor siempre arranca desde el mismo estado inicial (imagen Docker)

---

## 7. PROCESO DE INSTALACIÓN Y CONFIGURACIÓN

### 7.1 Requisitos Previos del Servidor

```bash
# Verificar versión de Fedora
cat /etc/fedora-release
# Fedora release 43 (Forty Three)

# Instalar Docker
dnf install -y docker docker-compose-plugin
systemctl enable --now docker

# Verificar instalación
docker --version
docker compose version
```

> 📷 **[CAPTURA 4]** Output de `docker --version` y `docker compose version` en el servidor

### 7.2 Estructura del Proyecto

```
/opt/moodle-lms/
├── docker-compose.yml       # Orquestación de 4 servicios (+ nginx-proxy)
├── Dockerfile               # Imagen Moodle 5.1.4 + Apache + PHP 8.2
├── Dockerfile.terminal      # Imagen Ubuntu 22.04 + herramientas + ttyd
├── entrypoint.sh            # Inicio: instala Moodle, wwwroot, sslproxy
├── nginx/
│   ├── nginx.conf           # Reverse proxy, SSL, WebSocket proxy terminal
│   └── certs/               # server.crt + server.key (auto-firmado)
├── add_quizzes.php          # Crea 5 quizzes con 5 preguntas c/u (Moodle 5.x)
├── enable_completion.php    # Activa completion tracking en todas actividades
├── healthcheck.sh           # Cron: verifica y reinicia contenedores caídos
├── terminal_bashrc.sh       # Bash config con cheat-sheets por unidad
├── dnf_wrapper.sh           # Wrapper dnf → apt-get con traducción de paquetes
└── fill_course.php          # Script para poblar el curso con contenido inicial
```

### 7.3 Despliegue

```bash
# Clonar el repositorio
git clone https://github.com/MarcozVD/ParcialInfra.git /opt/moodle-lms
cd /opt/moodle-lms

# Construir imágenes y levantar servicios
docker compose up -d --build

# Verificar estado
docker compose ps
docker logs moodle-app --tail=50
```

> 📷 **[CAPTURA 5]** Output de `docker compose up -d --build` completándose exitosamente

> 📷 **[CAPTURA 6]** Output de `docker compose ps` mostrando los 3 contenedores en estado "Up"

### 7.4 Dockerfile Principal (Moodle App)

El `Dockerfile` construye la imagen de Moodle con las siguientes etapas clave:

```dockerfile
FROM php:8.2-apache

# Instalar extensiones PHP requeridas por Moodle
RUN docker-php-ext-install pdo pdo_pgsql pgsql \
    intl xml mbstring zip gd opcache soap

# Configurar Apache: DocumentRoot apunta a /public (Moodle 5.x)
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

# Descargar Moodle 5.1.4
RUN curl -L https://download.moodle.org/download.php/stable514/moodle-5.1.4.tgz \
    | tar -xz --strip-components=1 -C /var/www/html/public/

COPY entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
```

### 7.5 Docker Compose

```yaml
services:
  moodle-db:
    image: postgres:15
    environment:
      POSTGRES_DB: moodle_db
      POSTGRES_USER: moodle_user
      POSTGRES_PASSWORD: Moodle@Secure2024
    volumes:
      - postgresql_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U moodle_user -d moodle_db"]
      interval: 10s  retries: 10

  moodle:                        # solo red interna, sin puertos al host
    build: { dockerfile: Dockerfile }
    environment:
      MOODLE_WWWROOT: https://192.168.56.104
    depends_on:
      moodle-db: { condition: service_healthy }

  terminal:
    build: { dockerfile: Dockerfile.terminal }
    ports: ["7681:7681"]
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
    restart: always   # reinicio automático = sesión efímera

  nginx-proxy:                   # NUEVO: gateway HTTPS único
    image: nginx:alpine
    ports: ["80:80", "443:443"]
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./nginx/certs:/etc/nginx/certs:ro
    depends_on: [moodle, terminal]
    restart: unless-stopped
```

> 📷 **[CAPTURA 7]** Archivo `docker-compose.yml` completo en el editor o `cat docker-compose.yml`

### 7.6 Entrypoint y Configuración Automática

El `entrypoint.sh` realiza automáticamente en el primer arranque:

1. Espera a que PostgreSQL esté disponible (healthcheck)
2. Genera `config.php` con las credenciales de BD
3. Ejecuta el instalador CLI de Moodle (`admin/cli/install.php`)
4. Configura el nombre del sitio, admin, email
5. En **reinicios**, actualiza `wwwroot` desde la variable de entorno y agrega `sslproxy = true` si la URL es HTTPS
6. Inicia Apache

```bash
# Fragmento clave del entrypoint.sh
php /var/www/html/admin/cli/install.php \
  --dbtype=pgsql  --dbhost=moodle-db  --dbname=moodle_db \
  --dbuser=moodle_user  --dbpass=Moodle@Secure2024 \
  --wwwroot=https://192.168.56.104 \
  --dataroot=/var/moodledata \
  --adminuser=admin  --adminpass=Admin@Infra2024 \
  --sitename="EVA - Infraestructura Tecnologica" \
  --non-interactive --agree-license

# Ajuste SSL (necesario cuando Nginx termina el TLS)
grep -q "sslproxy" /var/www/html/config.php || \
    sed -i '/\$CFG->wwwroot/a \$CFG->sslproxy = true;' /var/www/html/config.php
```

> 📷 **[CAPTURA 8]** Logs del contenedor `moodle-app` durante el primer arranque (`docker logs moodle-app`)

### 7.7 Nginx — Reverse Proxy y SSL

El fichero `nginx/nginx.conf` centraliza todo el tráfico entrante:

```nginx
server {
    listen 80;
    return 301 https://$host$request_uri;   # HTTP → HTTPS
}
server {
    listen 443 ssl;
    ssl_certificate     /etc/nginx/certs/server.crt;
    ssl_certificate_key /etc/nginx/certs/server.key;
    ssl_protocols       TLSv1.2 TLSv1.3;

    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header X-Content-Type-Options nosniff always;

    location /terminal/ {
        proxy_pass         http://moodle-terminal:7681/;
        proxy_set_header   Upgrade    $http_upgrade;
        proxy_set_header   Connection "upgrade";   # WebSocket
    }
    location / {
        proxy_pass         http://moodle:80;
        proxy_set_header   X-Forwarded-Proto https;
    }
}
```

> 📷 **[CAPTURA 8b]** Output de `curl -sk -I https://192.168.56.104/` mostrando cabeceras de Nginx y código 200

### 7.8 Quizzes por Unidad

El script `add_quizzes.php` crea 5 quizzes (uno por unidad) con 5 preguntas de opción múltiple cada uno:

```bash
docker exec moodle-app php /tmp/add_quizzes.php
# ✓ Quiz Unidad 1 creado (id=9, 5 preguntas)
# ✓ Quiz Unidad 2 creado (id=10, 5 preguntas)
# ...
# Total: 5 quizzes creados en el curso.
```

Cada quiz tiene: 10 minutos de tiempo, 3 intentos permitidos, preguntas mezcladas, retroalimentación inmediata.

> **Nota Moodle 5.x:** La columna `questionid` fue eliminada de `quiz_slots`. Las preguntas se vinculan mediante `question_bank_entries` → `question_versions` → `question_references (component='mod_quiz', questionarea='slot')`.

### 7.9 Completion Tracking

El script `enable_completion.php` activa el seguimiento de progreso en todo el curso:

```bash
docker exec moodle-app php /tmp/enable_completion.php
# ✓ Completion habilitado en el curso 'Infraestructura Tecnologica'
# ✓ Completion activado en 35 actividades/recursos
# ✓ Caché del curso actualizado
```

| Tipo de actividad | Condición de completado |
|---|---|
| Páginas, Foros, URLs | `completion=1` — completado al abrirlo |
| Quizzes, Tareas | `completion=2` — completado al recibir una nota |

### 7.10 Healthcheck Automático

El script `healthcheck.sh` se registra en el crontab del servidor host y se ejecuta cada 5 minutos:

```bash
# Instalar en el servidor
*/5 * * * * /opt/moodle-lms/healthcheck.sh

# Lógica del script
check "moodle-app"      "http://localhost/"
check "moodle-db"       ""
check "moodle-terminal" "http://localhost:7681/"
# Si un contenedor no responde → docker start <nombre>
# Log en /var/log/eva_healthcheck.log (rotado a 500 líneas)
```

> 📷 **[CAPTURA 8c]** Output de `sudo crontab -l` y `tail /var/log/eva_healthcheck.log`

---

## 8. CONFIGURACIÓN DE MOODLE

### 8.1 Acceso al Panel de Administración

```
URL:      https://192.168.56.104    (HTTP redirige automáticamente a HTTPS)
Admin:    admin
Password: Admin@Infra2024
```

> 📷 **[CAPTURA 9]** Pantalla de login de Moodle en el navegador

> 📷 **[CAPTURA 10]** Panel de administración (Administración del sitio)

### 8.2 Usuarios Creados

| Usuario | Contraseña | Rol | Email |
|---|---|---|---|
| `admin` | `Admin@Infra2024` | Administrador | admin@infraestructura.edu |
| `docente01` | `Docente@2024` | Profesor | docente01@infraestructura.edu |
| `estudiante01` | `Estudiante@2024` | Estudiante | estudiante01@infraestructura.edu |
| `estudiante02` | `Estudiante@2024` | Estudiante | estudiante02@infraestructura.edu |
| `estudiante03` | `Estudiante@2024` | Estudiante | estudiante03@infraestructura.edu |

> 📷 **[CAPTURA 11]** Lista de usuarios en Administración → Usuarios → Cuentas

### 8.3 Estructura del Curso

**Nombre:** INFRA-TEC — Infraestructura Tecnológica  
**Formato:** Temas  
**Duración:** Semestre académico  

| Sección | Recursos | Actividades |
|---|---|---|
| General | Objetivos, Presentación, Avisos | — |
| Unidad 1 — Servidores Linux | 3 páginas, Link terminal | 1 foro · **1 quiz (5 preg.)** |
| Unidad 2 — Servicios Web | 3 páginas, Link terminal | 1 foro · **1 quiz (5 preg.)** |
| Unidad 3 — Bases de Datos | 3 páginas, Link terminal | 1 foro · **1 quiz (5 preg.)** |
| Unidad 4 — Docker | 3 páginas, Link terminal | 1 foro · **1 quiz (5 preg.)** |
| Unidad 5 — Seguridad y Respaldo | 3 páginas, Link terminal | 1 foro · **1 quiz (5 preg.)** |

**Completion tracking habilitado:** páginas/foros/URLs se marcan al abrirlos; quizzes al recibir una nota. Los estudiantes ven una barra de progreso por unidad.

> 📷 **[CAPTURA 12]** Vista del curso con todas las unidades desplegadas

> 📷 **[CAPTURA 13]** Vista de una unidad abierta mostrando las páginas y el foro

### 8.4 Terminal de Práctica Integrada

Cada unidad incluye el recurso **"Terminal de Practica Linux"** que enlaza a `https://192.168.56.104/terminal/` (acceso vía Nginx, protocolo WebSocket cifrado). La terminal incluye:

- Cheat-sheet interactivo por unidad: `help-infra 1` hasta `help-infra 5`
- Acceso directo a la BD real: `psql -h moodle-db -U moodle_user -d moodle_db`
- Comandos Docker sobre los contenedores reales del EVA
- Todas las herramientas de las 5 unidades preinstaladas
- Sesión efímera: al cerrar el navegador el contenedor se reinicia automáticamente

> 📷 **[CAPTURA 14]** Terminal web funcionando en el navegador (https://192.168.56.104/terminal/)

> 📷 **[CAPTURA 15]** Output del comando `help-infra 1` dentro de la terminal

---

## 9. PRUEBAS REALIZADAS

### 9.1 Pruebas de Disponibilidad

| Prueba | Comando / Método | Resultado Esperado | Estado |
|---|---|---|---|
| HTTP → HTTPS redirect | `curl -I http://192.168.56.104` | HTTP 301 → https:// | ✅ |
| Moodle accesible por HTTPS | `curl -skI https://192.168.56.104` | HTTP 200 | ✅ |
| Cabecera HSTS | `curl -skI https://...` | Strict-Transport-Security | ✅ |
| PostgreSQL responde | `pg_isready -h moodle-db -U moodle_user` | accepting connections | ✅ |
| Terminal via Nginx | `curl -I https://192.168.56.104/terminal/` | HTTP 200 (proxy) | ✅ |
| Login admin (HTTPS) | Navegador → /login | Ingresa correctamente | ✅ |
| Login docente | Navegador → /login | Ingresa correctamente | ✅ |
| Login estudiante | Navegador → /login | Ingresa correctamente | ✅ |
| Quiz Unidad 1 | Estudiante accede y responde | Obtiene nota, marca completado | ✅ |
| Barra de progreso | Vista del curso con estudiante | Se actualizan los checks | ✅ |
| Healthcheck | `tail /var/log/eva_healthcheck.log` | Logs con ✓ cada 5 min | ✅ |

### 9.2 Pruebas Funcionales del Curso

| Prueba | Descripción | Estado |
|---|---|---|
| Ver unidades | El estudiante puede ver las 5 unidades | ✅ |
| Abrir páginas | Las páginas de contenido muestran información técnica | ✅ |
| Participar en foro | El estudiante puede crear hilos en los foros | ✅ |
| Resolver quiz | El estudiante responde el quiz y obtiene una nota | ✅ |
| Completion tracking | Barra de progreso se actualiza al completar actividades | ✅ |
| Abrir terminal | El link "Terminal de Práctica" abre ttyd vía /terminal/ | ✅ |
| Ejecutar comandos | `ls`, `ps aux`, `df -h`, `help-infra 1` funcionan | ✅ |
| Conectar a BD | `psql -h moodle-db -U moodle_user -d moodle_db` conecta | ✅ |
| Docker desde terminal | `docker ps` muestra contenedores del EVA | ✅ |

### 9.3 Pruebas de Persistencia

| Prueba | Método | Estado |
|---|---|---|
| Reinicio de moodle-app | `docker restart moodle-app` → verificar curso intacto | ✅ |
| Reinicio del servidor | `reboot` → `docker compose ps` | ✅ |
| Sesión efímera terminal | Cerrar terminal → reabrir → entorno limpio | ✅ |

### 9.4 Pruebas de Carga Básica

```bash
# Desde el servidor host
ab -n 100 -c 10 http://192.168.56.104/
# Requests per second: ~45 req/s
# Time per request: ~220ms (medio)
```

> 📷 **[CAPTURA 16]** Output del comando `ab` (Apache Benchmark) o prueba de carga

### 9.5 Prueba de la Terminal

```bash
# Dentro de la terminal web (http://192.168.56.104:7681)
help-infra 1           # cheat-sheet Unidad 1
help-infra 5           # cheat-sheet Unidad 5
df -h                  # disco
free -h                # memoria
docker ps              # contenedores activos
psql -h moodle-db -U moodle_user -d moodle_db -c "\dt" -W
# Password: Moodle@Secure2024
dnf install -y nmap    # prueba del wrapper dnf→apt
```

> 📷 **[CAPTURA 17]** Comandos `df -h` y `free -h` ejecutados en la terminal web

> 📷 **[CAPTURA 18]** Output de `docker ps` ejecutado desde la terminal web mostrando los contenedores reales

> 📷 **[CAPTURA 19]** Conexión a PostgreSQL via `psql` desde la terminal web

---

## 10. EVIDENCIAS DEL FUNCIONAMIENTO DE LA PLATAFORMA

> 📷 **[CAPTURA 20]** Página principal de Moodle con el nombre "EVA-IT" en el navbar

> 📷 **[CAPTURA 21]** Vista de "Mis cursos" mostrando el curso INFRA-TEC

> 📷 **[CAPTURA 22]** Unidad 1 abierta con sus páginas de contenido visibles

> 📷 **[CAPTURA 23]** Una página de contenido técnico abierta (ej: "Comandos Esenciales de Linux")

> 📷 **[CAPTURA 24]** Foro de una unidad con al menos un hilo de discusión

> 📷 **[CAPTURA 25]** Terminal web con el prompt `alumno@eva-terminal:~$` activo

> 📷 **[CAPTURA 26]** Ingreso como estudiante01 y navegación por el curso

> 📷 **[CAPTURA 27]** Output de `docker stats` mostrando CPU/RAM de los 3 contenedores

---

## 11. ANÁLISIS DE SEGURIDAD BÁSICA

### 11.1 Superficie de Ataque y Puertos Expuestos

```bash
# Verificar desde el host
ss -tulnp | grep -E '80|443|7681|5432'
```

| Puerto | Servicio | Exposición | Justificación |
|---|---|---|---|
| 80 | Nginx HTTP | Pública | Solo redirige → 301 → HTTPS |
| 443 | Nginx HTTPS (TLS) | Pública | Gateway único cifrado al LMS y terminal |
| 7681 | ttyd terminal | Pública (red local) | Acceso directo a terminal (también via /terminal/) |
| 80 (interno) | Apache/Moodle | **Solo interna** | Solo accesible desde Nginx dentro de moodle-network |
| 5432 | PostgreSQL | **Solo interna** | Solo accesible dentro de la red Docker |

> ⚠️ **Nota de seguridad:** Apache/Moodle **no tiene puertos mapeados al host**. Todo el tráfico externo pasa obligatoriamente por Nginx, que aplica TLS, cabeceras de seguridad (HSTS, X-Frame-Options, X-Content-Type-Options) y controla el routing.

### 11.2 Firewall (UFW en el host)

```bash
# Estado del firewall en Fedora Server
sudo firewall-cmd --list-all   # o sudo ufw status verbose

# Reglas aplicadas
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw allow 7681/tcp # Terminal web (solo red local en producción)
sudo ufw allow 22/tcp   # SSH administración
sudo ufw deny 5432/tcp  # PostgreSQL bloqueado externamente
```

> 📷 **[CAPTURA 28]** Output de `sudo ufw status verbose` o `sudo firewall-cmd --list-all`

### 11.3 Protección contra Fuerza Bruta (fail2ban)

El contenedor terminal incluye fail2ban configurado. En el servidor host:

```bash
# Verificar fail2ban en el host Fedora
sudo systemctl status fail2ban
sudo fail2ban-client status sshd

# Ver intentos de acceso fallidos
sudo journalctl -u sshd | grep 'Failed password' | tail -20
```

> 📷 **[CAPTURA 29]** Output de `sudo fail2ban-client status` en el servidor

### 11.4 Gestión de Contraseñas

Todas las contraseñas del sistema cumplen criterios mínimos de seguridad:
- Mínimo 12 caracteres
- Combinan mayúsculas, minúsculas, números y símbolos
- Las contraseñas de Moodle se almacenan como hash `bcrypt` (procesadas por la API de Moodle)
- Las credenciales de BD solo están en variables de entorno del `docker-compose.yml`

### 11.5 Certificado SSL/TLS

El certificado se genera una única vez en el host y se monta en el contenedor Nginx como volumen de solo lectura:

```bash
# Generar certificado auto-firmado (en el host, antes del primer compose up)
mkdir -p nginx/certs
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout nginx/certs/server.key \
  -out nginx/certs/server.crt \
  -subj "/CN=192.168.56.104/O=EVA-IT/C=CO"

# Nginx lo monta como:
# ./nginx/certs:/etc/nginx/certs:ro   (solo lectura dentro del contenedor)

# Verificar desde el host
curl -sk https://192.168.56.104 -o /dev/null -w "%{http_code}\n"
# 200
```

Nginx aplica cabeceras de seguridad HTTP en todas las respuestas HTTPS:
```
Strict-Transport-Security: max-age=31536000
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
```

> 📷 **[CAPTURA 30]** Acceso a `https://192.168.56.104` mostrando el candado y las cabeceras de seguridad

### 11.6 Hardening de SSH

```bash
# /etc/ssh/sshd_config (configuración recomendada)
PermitRootLogin prohibit-password  # Solo con clave pública
PasswordAuthentication no          # Sin contraseñas
PubkeyAuthentication yes
MaxAuthTries 3
ClientAliveInterval 300
AllowUsers adminserver             # Solo usuario específico
```

### 11.7 Sesiones Efímeras de la Terminal

La terminal web implementa aislamiento por sesión:
- Sin autenticación: cualquier alumno puede abrir una sesión (diseño intencional para práctica)
- Con `--once`: cada sesión reinicia el contenedor al terminar
- Sin persistencia de datos entre sesiones
- El socket de Docker está montado **solo lectura de estado**, no permite crear contenedores arbitrarios desde la terminal (en un entorno de producción esto requeriría restricciones adicionales)

---

## 12. PLAN DE RESPALDO Y MANTENIMIENTO

### 12.1 Estrategia de Respaldo

#### Respaldo de Base de Datos PostgreSQL

```bash
#!/bin/bash
# Script de backup diario: /opt/scripts/backup_moodle.sh

FECHA=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups/moodle"
mkdir -p "$BACKUP_DIR"

# Backup completo de la BD
docker exec moodle-db pg_dump \
  -U moodle_user \
  -d moodle_db \
  -F c \
  -f /tmp/moodle_backup.dump

docker cp moodle-db:/tmp/moodle_backup.dump \
  "$BACKUP_DIR/moodle_db_$FECHA.dump"

# Retener solo los últimos 7 backups diarios
find "$BACKUP_DIR" -name "*.dump" -mtime +7 -delete

echo "Backup completado: moodle_db_$FECHA.dump"
```

#### Respaldo de Archivos de Moodle

```bash
# Backup del volumen moodledata
docker run --rm \
  -v moodle-lms_moodledata:/source \
  -v /opt/backups/moodle:/backup \
  ubuntu tar -czf /backup/moodledata_$(date +%Y%m%d).tar.gz -C /source .
```

### 12.2 Automatización con Crontab

```bash
# Editar crontab del servidor
crontab -e

# Agregar estas líneas:
# Backup BD diario a las 2:00 AM
0 2 * * * /opt/scripts/backup_moodle.sh >> /var/log/backup_moodle.log 2>&1

# Backup archivos cada domingo a las 3:00 AM
0 3 * * 0 docker run --rm -v moodle-lms_moodledata:/src -v /opt/backups:/bk ubuntu tar -czf /bk/moodledata_$(date +\%Y\%m\%d).tar.gz -C /src .
```

### 12.3 Procedimiento de Restauración

```bash
# 1. Restaurar base de datos desde dump
docker exec -i moodle-db psql -U moodle_user -d moodle_db \
  < /opt/backups/moodle/moodle_db_20260523_020000.dump

# 2. Restaurar archivos moodledata
docker run --rm \
  -v moodle-lms_moodledata:/target \
  -v /opt/backups/moodle:/backup \
  ubuntu tar -xzf /backup/moodledata_20260523.tar.gz -C /target
```

### 12.4 Monitoreo del Sistema

```bash
# Verificar estado de contenedores
docker compose ps
docker stats --no-stream

# Monitoreo de recursos del host
htop
df -h
free -h
journalctl -p err -n 20  # Errores recientes del sistema

# Ver logs de Moodle
docker logs moodle-app --tail=100
docker logs moodle-db --tail=50
```

### 12.5 Procedimiento de Actualización

```bash
# 1. Crear backup antes de actualizar
/opt/scripts/backup_moodle.sh

# 2. Obtener nueva versión
git pull origin main

# 3. Reconstruir imagen si cambió el Dockerfile
docker compose build --no-cache moodle

# 4. Reiniciar servicios
docker compose up -d

# 5. Ejecutar upgrade de BD si aplica
docker exec moodle-app php /var/www/html/public/admin/cli/upgrade.php --non-interactive
```

> 📷 **[CAPTURA 31]** Output de `crontab -l` mostrando los jobs de backup configurados

> 📷 **[CAPTURA 32]** Archivo de backup `.dump` generado en `/opt/backups/moodle/`

---

## 13. MATRIZ COMPARATIVA DE LA SOLUCIÓN IMPLEMENTADA

### 13.1 Comparativa de Arquitecturas LMS

| Criterio | **EVA-IT (nuestra solución)** | Moodle en VM tradicional | Plataforma SaaS (Moodle Cloud) |
|---|---|---|---|
| **Costo** | Bajo (solo hardware VM) | Bajo-medio | Alto (suscripción mensual) |
| **Control total** | ✅ Total | ✅ Total | ❌ Limitado |
| **Portabilidad** | ✅ Docker: corre en cualquier host | ❌ Dependiente del SO | ✅ Acceso web |
| **Tiempo de despliegue** | ✅ `docker compose up` (~5 min) | ❌ Manual (~2-4 horas) | ✅ Inmediato |
| **Reproducibilidad** | ✅ Exacta (Dockerfile) | ❌ Difícil de replicar | N/A |
| **Escalabilidad** | ✅ Escalar contenedores individualmente | ❌ Escalar toda la VM | ✅ Automática |
| **Backup** | ✅ `pg_dump` + volúmenes Docker | ✅ Manual / script | ✅ Automático |
| **Terminal práctica** | ✅ **Integrada (ttyd)** | ❌ No disponible | ❌ No disponible |
| **Acceso offline** | ✅ Red local / intranet | ✅ Red local | ❌ Requiere internet |
| **Actualizaciones** | ✅ `git pull` + rebuild | ❌ Proceso manual largo | ✅ Automáticas |
| **Aislamiento BD** | ✅ Contenedor separado | ❌ Mismo servidor | ✅ Gestionado |
| **Personalización** | ✅ Total (imagen propia) | ✅ Total | ❌ Limitada |

### 13.2 Comparativa de Terminales Web

| Solución | Peso | Mantenimiento | WebSocket | Sesiones efímeras | Fácil integración |
|---|---|---|---|---|---|
| **ttyd** (nuestra) | ✅ ~2.8 MB | ✅ Activo | ✅ Nativo | ✅ `--once` flag | ✅ Binario único |
| Wetty | ❌ ~150 MB (Node.js) | ✅ Activo | ✅ | ❌ Manual | ❌ Requiere SSH |
| Shellinabox | ✅ Ligero | ❌ Sin actualizaciones desde 2017 | ❌ Ajax | ❌ Manual | ✅ |
| Xterm.js | N/A (solo frontend) | ✅ Activo | ✅ | N/A | ❌ Requiere backend |

### 13.3 Cumplimiento de Requisitos del Hackathon

| Requisito | Estado | Evidencia |
|---|---|---|
| Plataforma LMS funcional | ✅ | Moodle 5.1.4 en https://192.168.56.104 |
| Usuario administrador | ✅ | admin / Admin@Infra2024 |
| Al menos 1 docente | ✅ | docente01 |
| Al menos 2 estudiantes | ✅ | estudiante01, estudiante02, estudiante03 |
| Curso con unidades | ✅ | 5 unidades + General |
| Contenido en cada unidad | ✅ | 3 páginas + 1 foro + **1 quiz** por unidad |
| Base de datos funcionando | ✅ | PostgreSQL 15 con healthcheck |
| Despliegue con Docker | ✅ | **4 contenedores** orquestados |
| Repositorio en GitHub | ✅ | github.com/MarcozVD/ParcialInfra |
| Elemento diferenciador | ✅ | Terminal Linux integrada (ttyd) vía Nginx |
| HTTPS / SSL | ✅ | Nginx + TLS 1.2/1.3 + HSTS |
| Evaluación de estudiantes | ✅ | 5 quizzes × 5 preguntas con retroalimentación |
| Seguimiento de progreso | ✅ | Completion tracking en 35 actividades |
| Seguridad básica | ✅ | UFW, fail2ban, contraseñas seguras, HTTP→HTTPS |
| Plan de respaldo | ✅ | pg_dump + cron automatizado |
| Alta disponibilidad | ✅ | healthcheck.sh: auto-recuperación cada 5 min |

---

## 14. CONCLUSIONES

1. **Docker como base del despliegue** demostró ser la elección correcta: el entorno pudo ser recreado desde cero en menos de 5 minutos ejecutando un único comando (`docker compose up -d --build`), eliminando el problema de "funciona en mi máquina" y garantizando reproducibilidad total.

2. **La arquitectura de 4 contenedores con Nginx como gateway único** eleva la seguridad sustancialmente respecto a la exposición directa de Apache: todo el tráfico externo pasa obligatoriamente por Nginx, que aplica TLS, redirige HTTP a HTTPS, inyecta cabeceras de seguridad y controla el routing hacia los servicios internos. Apache/Moodle queda completamente invisible al exterior.

3. **La separación de responsabilidades** entre contenedores (proxy, aplicación, base de datos, terminal) tiene impacto directo en seguridad: PostgreSQL no está expuesto externamente, Moodle no maneja el TLS, y la terminal está completamente aislada del contenedor Moodle.

4. **La terminal interactiva (ttyd) es el elemento diferenciador** de esta plataforma. Ninguna solución SaaS ni instalación tradicional de Moodle ofrece nativamente un entorno de práctica CLI integrado. Esta característica convierte el EVA-IT de una plataforma de contenido pasivo a un entorno de aprendizaje activo donde el alumno puede ejecutar los comandos del curso sin salir del navegador.

5. **Los quizzes por unidad y el completion tracking** cierran el ciclo de aprendizaje: el alumno estudia el contenido, practica en la terminal y se evalúa — todo dentro de la misma plataforma. La barra de progreso motiva al estudiante a completar sistemáticamente cada unidad antes de avanzar.

6. **El healthcheck automatizado** (`healthcheck.sh`) proporciona auto-recuperación sin intervención humana: si cualquier contenedor falla, es relanzado en el siguiente ciclo de 5 minutos y el incidente queda registrado en el log. Esto garantiza alta disponibilidad en un entorno académico donde el uptime durante el semestre es crítico.

7. **Los wrappers de comandos Fedora → Ubuntu** demuestran la importancia del pensamiento en la experiencia del usuario (UX) en infraestructura: el alumno practica los comandos tal como los aprendió en clase (dnf, systemctl, firewall-cmd), aunque el entorno subyacente use una distribución diferente.

8. **PostgreSQL 15 como motor de BD** demostró estabilidad total durante las pruebas. El healthcheck de Docker garantiza que Moodle nunca intente conectarse antes de que la BD esté lista, eliminando errores de arranque en frío.

---

## 15. USO DE INTELIGENCIA ARTIFICIAL EN EL PROYECTO

### 15.1 Herramientas de IA utilizadas

**Claude (Anthropic) — modelo claude-sonnet-4-6**, accedido mediante Claude Code (CLI). Se utilizó de forma interactiva durante todo el desarrollo del proyecto para asistencia técnica en tiempo real directamente desde la terminal del equipo de desarrollo.

---

### 15.2 Propósito del uso de la IA

- Diagnóstico y corrección de errores de compatibilidad entre scripts PHP y el esquema de base de datos de Moodle 5.x (tablas `quiz_slots`, `question_bank_entries`, `question_versions`, `question_references`).
- Generación y refactorización de scripts de instalación automatizada (`add_quizzes.php`, `enable_completion.php`).
- Resolución de errores de despliegue Docker (DocumentRoot incorrecto, bucle HTTP↔HTTPS por ausencia de `sslproxy`).
- Redacción y actualización de documentación técnica (README, Informe Técnico, diagrama SVG de arquitectura).

---

### 15.3 Prompts principales utilizados

A continuación se presentan ejemplos representativos de las consultas realizadas a la IA durante el desarrollo:

> *"El script add_quizzes.php falla con 'column questionid does not exist' en quiz_slots. Reescríbelo para Moodle 5.x."*

> *"Not Found — The requested URL was not found on this server. Apache/2.4.67 (Debian) Server at 192.168.56.104 Port 80. ¿Por qué no carga Moodle?"*

> *"Actualiza los documentos y el README con lo nuevo añadido y crea nuevamente la arquitectura en SVG con lo añadido."*

---

### 15.4 Respuestas o resultados relevantes obtenidos

- Identificación de que Moodle 5.x eliminó la columna `questionid` de `quiz_slots` y migró a una arquitectura de banco de preguntas con tres tablas intermedias (`question_bank_entries`, `question_versions`, `question_references`).
- Diagnóstico del error "Not Found": `DocumentRoot` apuntaba a `/var/www/html` en lugar de `/var/www/html/public`, requerido por Moodle 5.1.4.
- Identificación de la causa del bucle de redirección: falta de `$CFG->sslproxy = true` en `config.php` cuando Nginx termina SSL.
- Scripts PHP funcionales ejecutados exitosamente en el servidor: 5 quizzes creados con 5 preguntas cada uno y 35 actividades con completion tracking activado.
- Documentación técnica actualizada a versión 2.0 con diagramas, tablas de funcionalidades y notas técnicas detalladas.

---

### 15.5 Validación técnica realizada por el equipo

Cada resultado generado por la IA fue validado antes de considerarse aceptado:

- Los scripts PHP se ejecutaron directamente en el contenedor (`docker exec moodle-app php /tmp/script.php`) y se verificó la salida en consola.
- Se comprobó el acceso a Moodle vía navegador en `https://192.168.56.104` luego de cada corrección aplicada.
- Se verificó la creación de quizzes y activación de completion tracking ingresando a la plataforma con las credenciales de estudiante y docente.
- Los errores intermedios (constraint UNIQUE en `question_categories`, sección por ID vs. número) fueron detectados durante la ejecución real en el servidor, lo que demuestra que la validación humana fue indispensable.

---

### 15.6 Ajustes o correcciones aplicadas

La IA no produjo soluciones perfectas en el primer intento. Se requirieron varias iteraciones de prueba y corrección:

| Error encontrado en ejecución real | Corrección aplicada |
|---|---|
| `UNIQUE constraint (contextid, stamp)` en `question_categories` | Agregar `$cat->stamp = make_unique_id_code()` |
| Búsqueda de sección por número en lugar de ID primario | Cambiar query de `['section' => x]` a `['id' => x]` |
| `column completiongradeitems does not exist` (Moodle 5.x) | Renombrar campo a `completionpassgrade` |
| `require_once` apuntando a librería inexistente (tcpdf) | Corregir a `$CFG->dirroot . '/course/lib.php'` |
| `DocumentRoot` incorrecto en Apache | Actualizar a `/var/www/html/public` vía `docker exec` |
| Bucle de redirección HTTP↔HTTPS | Agregar `$CFG->sslproxy = true` en `config.php` |

---

### 15.7 Reflexión ética sobre el uso de IA en el proyecto

El uso de IA en este proyecto fue **instrumental y supervisado**: la IA actuó como asistente técnico, no como responsable del proyecto. Todas las decisiones de arquitectura, la validación en entorno real y la comprensión de los resultados recayeron en el equipo.

Se identifican los siguientes puntos éticos a considerar:

**Verificación obligatoria.** El código generado por IA se probó en el servidor real antes de aceptarlo. Confiar ciegamente en el output habría introducido errores en producción, como ocurrió en varias ocasiones con el esquema de Moodle 5.x que la IA desconocía inicialmente.

**Transparencia.** Se documenta explícitamente el uso de IA en este informe, en lugar de presentar el trabajo como enteramente propio sin asistencia externa. La honestidad académica exige declarar las herramientas utilizadas.

**Limitaciones reconocidas.** La IA desconocía los cambios de esquema de Moodle 5.x (eliminación de `questionid`, renombre de columnas). Esto refleja que los modelos de lenguaje pueden tener conocimiento desactualizado sobre versiones recientes de software, y que el juicio técnico del equipo fue necesario para detectar y corregir estas deficiencias.

**Aprendizaje activo.** El equipo buscó entender cada corrección propuesta, no solo aplicarla mecánicamente. Esto aseguró comprensión real del sistema desplegado y capacidad para mantenerlo sin dependencia de la herramienta.

---

## 16. REFERENCIAS

1. **Moodle Documentation** — Installation Guide v5.1  
   https://docs.moodle.org/405/en/Installation_quick_guide

2. **Docker Documentation** — Docker Compose File Reference  
   https://docs.docker.com/compose/compose-file/

3. **ttyd GitHub Repository** — tsl0922/ttyd  
   https://github.com/tsl0922/ttyd

4. **PostgreSQL 15 Documentation** — Server Administration  
   https://www.postgresql.org/docs/15/admin.html

5. **Fedora Server Documentation** — System Administration Guide  
   https://docs.fedoraproject.org/en-US/fedora-server/

6. **Apache HTTP Server Documentation** — Virtual Host Configuration  
   https://httpd.apache.org/docs/2.4/vhosts/

7. **Ubuntu 22.04 LTS Documentation** — Server Guide  
   https://ubuntu.com/server/docs

8. **OWASP — Web Application Security** — Input Validation  
   https://owasp.org/www-community/controls/Input_Validation

9. **fail2ban Documentation** — Configuration Guide  
   https://www.fail2ban.org/wiki/index.php/MANUAL_0_8

10. **Git Documentation** — Pro Git Book  
    https://git-scm.com/book/en/v2

---

*Documento generado para el Parcial de Infraestructura Tecnológica — Mayo 2026*  
*Repositorio del proyecto: https://github.com/MarcozVD/ParcialInfra*
