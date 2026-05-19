# Documento Técnico - Plataforma LMS Moodle
## Hackathon Infraestructura Tecnológica

---

## Portada

| Campo | Detalle |
|---|---|
| **Proyecto** | Implementación de Plataforma LMS basada en Moodle |
| **Asignatura** | Infraestructura Tecnológica |
| **Rol del equipo** | Empresa Consultora de Infraestructura Tecnológica |
| **Fecha** | Mayo 2026 |
| **URL de acceso** | http://192.168.56.104 |
| **Versión Moodle** | 5.1.4 (Build: 20260420) |

---

## 1. Introducción

Este documento técnico describe el diseño, implementación y configuración de una plataforma **LMS (Learning Management System)** basada en **Moodle 5.1.4**, desarrollada como solución de infraestructura tecnológica para la asignatura Infraestructura Tecnológica.

La solución implementada adopta una arquitectura de contenedores Docker, utilizando **Apache HTTP Server**, **PostgreSQL 15** y **Moodle 5.1.4** sobre un servidor **Fedora Linux 43 Server Edition**. Se tomaron decisiones técnicas fundamentadas en criterios de rendimiento, escalabilidad, compatibilidad y facilidad de administración.

---

## 2. Objetivos

### Objetivo General
Diseñar e implementar una plataforma LMS funcional basada en Moodle 5.1.4, desplegada mediante contenedores Docker sobre Fedora Server, que soporte los procesos académicos de la asignatura Infraestructura Tecnológica.

### Objetivos Específicos
1. Configurar un entorno de contenedores Docker con separación de servicios (web y base de datos).
2. Instalar y configurar Moodle 5.1.4 con soporte completo para idioma español.
3. Crear el curso "Infraestructura Tecnológica" con contenido mínimo requerido.
4. Gestionar usuarios con diferentes roles (administrador, docente, estudiantes).
5. Aplicar medidas básicas de seguridad en la infraestructura.
6. Documentar el proceso de instalación de forma reproducible.

---

## 3. Descripción de la Arquitectura

### 3.1 Diagrama de Arquitectura

```
+------------------------------------------------------------------+
|              FEDORA LINUX 43 SERVER (192.168.56.104)             |
|                                                                  |
|   +-----------------------+    +----------------------------+    |
|   |   CONTENEDOR Docker   |    |   CONTENEDOR Docker        |    |
|   |   moodle-app          |    |   moodle-db                |    |
|   |                       |    |                            |    |
|   |  PHP 8.2 + Apache     |    |   PostgreSQL 15            |    |
|   |  Moodle 5.1.4         |    |                            |    |
|   |  Puerto: 80→80        |    |   Puerto: 5432 (interno)   |    |
|   |  Puerto: 443→443      |    |                            |    |
|   |                       |    |                            |    |
|   |  Vol: moodledata      |    |   Vol: postgresql_data     |    |
|   +-----------+-----------+    +------------+---------------+    |
|               |                             |                    |
|               +-----------------------------+                    |
|               |   Red Docker: moodle-network (bridge)           |
|               |                                                  |
|   +-----------+-------------------------------------------+      |
|   |              firewalld (HTTP/HTTPS permitido)          |      |
|   +-------------------------------------------------------+      |
+------------------------------------------------------------------+
                              |
                         Internet / LAN
                              |
                        Navegadores web
                     (Docentes / Estudiantes)
```

### 3.2 Componentes de la Arquitectura

| Capa | Tecnología | Versión | Rol |
|---|---|---|---|
| Sistema Operativo | Fedora Linux | 43 Server | Host de contenedores |
| Contenedores | Docker + Docker Compose | 29.4.1 / v5.1.3 | Orquestación de servicios |
| Servidor Web | Apache HTTP Server | 2.4.67 (Debian) | Servir aplicación Moodle |
| Lenguaje | PHP | 8.2.31 | Runtime de Moodle |
| Base de Datos | PostgreSQL | 15 | Persistencia de datos |
| LMS | Moodle | 5.1.4 | Plataforma de aprendizaje |
| Imagen base | php:8.2-apache (Debian) | — | Base del contenedor Moodle |

---

## 4. Tecnologías Utilizadas

### 4.1 Fedora Linux 43 Server
Sistema operativo base del servidor. Elegido por su actualización constante, soporte de contenedores y herramientas de administración modernas (systemd, firewalld, SELinux).

### 4.2 Docker y Docker Compose
Plataforma de contenedores que permite aislar, portar y administrar los servicios de la aplicación de forma independiente del sistema operativo host. Versiones utilizadas:
- Docker Engine 29.4.1
- Docker Compose Plugin v5.1.3

### 4.3 Apache HTTP Server 2.4.67
Servidor web que procesa las peticiones HTTP/HTTPS y sirve la aplicación Moodle. Configurado con:
- `mod_rewrite`: para URLs limpias de Moodle
- DocumentRoot apuntando al directorio `public/` de Moodle 5.x
- Soporte para archivos `.htaccess`

### 4.4 PHP 8.2.31
Runtime de Moodle con las extensiones requeridas:
- `pdo_pgsql`, `pgsql`: Conexión a PostgreSQL
- `intl`, `mbstring`: Internacionalización
- `xml`, `xsl`, `soap`: Procesamiento XML
- `gd`: Procesamiento de imágenes
- `zip`: Compresión
- `opcache`: Caché de bytecode PHP

### 4.5 PostgreSQL 15
Motor de base de datos relacional. Moodle creó **489 tablas** en la base de datos `moodle_db`. Configuración:
- Base de datos: `moodle_db`
- Usuario: `moodle_user`
- Esquema: `public` con prefijo `mdl_`

### 4.6 Moodle 5.1.4
Plataforma LMS open source. Descargado desde el repositorio oficial de GitHub. Instalado mediante el CLI de Moodle (`admin/cli/install.php`).

---

## 5. Justificación de Decisiones Técnicas

### 5.1 ¿Por qué Fedora Server?
Fedora ofrece paquetes actualizados y es la base upstream de Red Hat Enterprise Linux. Tiene soporte nativo para Docker, systemd y firewalld, lo que facilita la administración de servicios y seguridad.

### 5.2 ¿Por qué Docker?
Docker permite:
- **Aislamiento**: Cada servicio (web, BD) corre en su propio contenedor independiente.
- **Portabilidad**: La solución puede replicarse en cualquier host con Docker.
- **Reproducibilidad**: El Dockerfile documenta exactamente cómo construir el entorno.
- **Escalabilidad**: Se pueden agregar réplicas o servicios sin afectar la infraestructura base.

### 5.3 ¿Por qué Apache y no Nginx?
Apache fue elegido porque:
- Moodle tiene compatibilidad nativa y documentada con Apache.
- El soporte para `.htaccess` (usado por Moodle) es nativo en Apache.
- Mayor compatibilidad con módulos PHP (mod_php vs FastCGI).

### 5.4 ¿Por qué PostgreSQL y no MySQL/MariaDB?
PostgreSQL fue seleccionado porque:
- Es el motor **recomendado por Moodle** para entornos de producción a largo plazo.
- Mayor integridad referencial estricta.
- Mejor manejo de concurrencia (MVCC).
- Moodle 5.x mejora el soporte nativo para PostgreSQL.

### 5.5 ¿Por qué imagen Docker personalizada?
La imagen oficial `bitnami/moodle` fue descontinuada de Docker Hub. Se construyó una imagen personalizada basada en `php:8.2-apache` (Debian), que permite:
- Control total sobre extensiones PHP instaladas.
- Selección de la versión exacta de Moodle.
- Configuración específica de Apache para Moodle 5.x.

### 5.6 Hallazgo crítico: Arquitectura de Moodle 5.x
Moodle 5.0 introdujo un cambio arquitectónico importante: el directorio `public/` como DocumentRoot web. Esto implica:
- `DocumentRoot`: `/var/www/html/public/` (archivos web accesibles)
- `config.php`: `/var/www/html/config.php` (fuera de public/, más seguro)
- `admin/cli/`: `/var/www/html/admin/cli/` (fuera del DocumentRoot)

---

## 6. Proceso de Instalación y Configuración

### 6.1 Preparación del servidor

```bash
# Detener servicios conflictivos
systemctl stop httpd && systemctl disable httpd

# Abrir puertos en firewall
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --reload

# Crear directorio del proyecto
mkdir -p /opt/moodle-lms
```

### 6.2 Dockerfile

```dockerfile
FROM php:8.2-apache

LABEL maintainer="EVA Infraestructura Tecnologica"

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev libxml2-dev libzip-dev libicu-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxslt1-dev postgresql-client \
    unzip curl cron \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP requeridas por Moodle
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql xml zip intl \
        gd mbstring soap opcache exif xsl

RUN a2enmod rewrite

# Configuración PHP para Moodle
RUN { \
    echo "max_execution_time = 360"; \
    echo "max_input_vars = 5000"; \
    echo "memory_limit = 256M"; \
    echo "upload_max_filesize = 50M"; \
    echo "post_max_size = 50M"; \
    echo "date.timezone = America/Bogota"; \
} > /usr/local/etc/php/conf.d/moodle.ini

# Apache: AllowOverride para Moodle
RUN printf '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/moodle.conf \
    && a2enconf moodle

# Descargar Moodle 5.1.4 desde GitHub
RUN curl -L https://github.com/moodle/moodle/archive/refs/tags/v5.1.4.tar.gz \
    | tar xz -C /var/www/html --strip-components=1 \
    && chown -R www-data:www-data /var/www/html

RUN mkdir -p /var/moodledata && chown www-data:www-data /var/moodledata

VOLUME ["/var/moodledata"]
EXPOSE 80

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
```

### 6.3 docker-compose.yml

```yaml
services:
  moodle-db:
    image: postgres:15
    container_name: moodle-db
    environment:
      POSTGRES_DB: moodle_db
      POSTGRES_USER: moodle_user
      POSTGRES_PASSWORD: Moodle@Secure2024
    volumes:
      - postgresql_data:/var/lib/postgresql/data
    networks:
      - moodle-network
    restart: unless-stopped
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U moodle_user -d moodle_db"]
      interval: 10s
      timeout: 5s
      retries: 10

  moodle:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: moodle-app
    ports:
      - "80:80"
      - "443:443"
    environment:
      MOODLE_DB_HOST: moodle-db
      MOODLE_DB_PORT: 5432
      MOODLE_DB_NAME: moodle_db
      MOODLE_DB_USER: moodle_user
      MOODLE_DB_PASS: Moodle@Secure2024
      MOODLE_WWWROOT: http://192.168.56.104
      MOODLE_ADMIN_PASS: Admin@Infra2024
      MOODLE_ADMIN_EMAIL: admin@infraestructura.edu
      MOODLE_SITE_NAME: EVA - Infraestructura Tecnologica
    volumes:
      - moodledata:/var/moodledata
    depends_on:
      moodle-db:
        condition: service_healthy
    networks:
      - moodle-network
    restart: unless-stopped

volumes:
  postgresql_data:
    driver: local
  moodledata:
    driver: local

networks:
  moodle-network:
    driver: bridge
```

### 6.4 Despliegue

```bash
cd /opt/moodle-lms

# Construir imagen (primera vez: ~8 minutos)
docker compose build --no-cache

# Levantar stack
docker compose up -d

# Verificar estado
docker ps

# Ver logs de Moodle
docker logs moodle-app --tail 20
```

### 6.5 Corrección de DocumentRoot (Moodle 5.x)

Moodle 5.x utiliza el directorio `public/` como DocumentRoot. Se requiere ajustar Apache:

```bash
# Cambiar DocumentRoot a public/
docker exec moodle-app sed -i \
  's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
  /etc/apache2/sites-enabled/000-default.conf

# Aplicar cambio en moodle.conf
docker exec moodle-app sed -i \
  's|<Directory /var/www/html>|<Directory /var/www/html/public>|g' \
  /etc/apache2/conf-enabled/moodle.conf

# Recargar Apache
docker exec moodle-app apache2ctl graceful
```

---

## 7. Configuración de Moodle

### 7.1 Instalación vía CLI
La instalación se realiza automáticamente mediante el entrypoint del contenedor:

```bash
php /var/www/html/admin/cli/install.php \
    --dbtype=pgsql \
    --dbhost=moodle-db \
    --dbport=5432 \
    --dbname=moodle_db \
    --dbuser=moodle_user \
    --dbpass=Moodle@Secure2024 \
    --dataroot=/var/moodledata \
    --wwwroot=http://192.168.56.104 \
    --adminuser=admin \
    --adminpass=Admin@Infra2024 \
    --adminemail=admin@infraestructura.edu \
    --fullname="EVA - Infraestructura Tecnologica" \
    --shortname=EVA-IT \
    --lang=es \
    --agree-license \
    --non-interactive
```

### 7.2 Usuarios creados

| Usuario | Contraseña | Rol | Nombre |
|---|---|---|---|
| `admin` | `Admin@Infra2024` | Administrador del sitio | Administrador Usuario |
| `docente01` | `Docente@2024` | Profesor (editingteacher) | Carlos Ramirez |
| `estudiante01` | `Estudiante@2024` | Estudiante | Ana Martinez |
| `estudiante02` | `Estudiante@2024` | Estudiante | Luis Torres |
| `estudiante03` | `Estudiante@2024` | Estudiante | Maria Lopez |

### 7.3 Curso creado

| Campo | Valor |
|---|---|
| Nombre completo | Infraestructura Tecnológica |
| Nombre corto | INFRA-TEC |
| Formato | Tópicos (7 secciones) |
| Idioma | Español |
| Inicio | 1 enero 2026 |

**Secciones del curso:**
- **General**: Presentación, objetivos y resultados de aprendizaje
- **Unidad 1**: Servidores Linux (instalación, administración, SSH, systemd)
- **Unidad 2**: Servicios Web (Apache, Nginx, Virtual Hosts, SSL)
- **Unidad 3**: Bases de Datos (MySQL, MariaDB, PostgreSQL)
- **Unidad 4**: Contenedores Docker (Dockerfile, Docker Compose, volúmenes)
- **Unidad 5**: Seguridad y Respaldo (firewalld, fail2ban, backups)
- **Evaluación Final**: Hackathon LMS (proyecto integrador)

**Actividades creadas:**
- Foro General - Noticias y Comunicación (sección 0)
- Proyecto Final - Implementación LMS (tarea evaluativa, sección 6)

---

## 8. Pruebas Realizadas

| Prueba | Resultado |
|---|---|
| Acceso HTTP a `http://192.168.56.104` | ✅ HTTP 200 |
| Página de login `http://192.168.56.104/login/index.php` | ✅ HTTP 200 |
| Login con usuario `admin` | ✅ Acceso exitoso |
| Visualización del curso "Infraestructura Tecnológica" | ✅ Correcto |
| Tablas en PostgreSQL | ✅ 489 tablas creadas |
| Contenedores Docker activos | ✅ moodle-app + moodle-db |
| Persistencia de datos (volúmenes Docker) | ✅ postgresql_data + moodledata |

---

## 9. Análisis de Seguridad Básica

### 9.1 Firewall (firewalld)
```bash
# Solo se permiten servicios estrictamente necesarios
firewall-cmd --list-services
# Resultado: cockpit dhcpv6-client http https ssh
```

### 9.2 Contenedores Docker
- **Aislamiento de red**: PostgreSQL solo es accesible desde la red interna Docker (`moodle-network`), no está expuesto al exterior.
- **Volúmenes con datos sensibles**: Los datos de PostgreSQL están en un volumen Docker named (`postgresql_data`), no directamente accesible desde el host.
- **Directivas Apache**: `Options -Indexes` deshabilita el listado de directorios.
- **config.php fuera del DocumentRoot**: En Moodle 5.x, el `config.php` está en `/var/www/html/` (fuera de `public/`), no accesible directamente por HTTP.

### 9.3 Contraseñas seguras
Todas las contraseñas cumplen requisitos mínimos:
- Mayúsculas, minúsculas, números y símbolos especiales.
- Longitud mínima de 12 caracteres.

### 9.4 Headers HTTP de Apache
Apache incluye por defecto headers como `X-Content-Type-Options` y configuración básica de seguridad en la imagen Debian base.

### 9.5 Mejoras recomendadas (pendientes para producción)
- Configurar HTTPS/SSL con certificado (Let's Encrypt o autofirmado).
- Implementar fail2ban para protección contra fuerza bruta SSH.
- Configurar `mod_security` en Apache.
- Habilitar SELinux con políticas específicas para Docker.
- Cambiar las contraseñas de ejemplo por contraseñas de producción.

---

## 10. Plan de Respaldo y Mantenimiento

### 10.1 Respaldo de Base de Datos PostgreSQL
```bash
# Backup diario de la BD (script en cron)
docker exec moodle-db pg_dump -U moodle_user moodle_db \
    > /opt/backups/moodle_db_$(date +%Y%m%d).sql

# Comprimir backup
gzip /opt/backups/moodle_db_$(date +%Y%m%d).sql
```

### 10.2 Respaldo de Archivos Moodle (moodledata)
```bash
# Backup del directorio de datos de Moodle
docker run --rm \
    -v moodle-lms_moodledata:/data \
    -v /opt/backups:/backup \
    alpine tar czf /backup/moodledata_$(date +%Y%m%d).tar.gz /data
```

### 10.3 Automatización con Cron (en Fedora host)
```bash
# Crontab para backups diarios a las 2:00 AM
0 2 * * * /opt/moodle-lms/scripts/backup.sh >> /var/log/moodle-backup.log 2>&1
```

### 10.4 Plan de Mantenimiento
- **Diario**: Verificar estado de contenedores (`docker ps`), revisar logs.
- **Semanal**: Revisar espacio en disco, revisar logs de Apache y PostgreSQL.
- **Mensual**: Actualizar imágenes Docker, aplicar parches de seguridad del OS.
- **Semestral**: Evaluar actualización de Moodle, revisión de políticas de seguridad.

### 10.5 Recuperación ante fallos
```bash
# Restaurar BD desde backup
docker exec -i moodle-db psql -U moodle_user moodle_db \
    < /opt/backups/moodle_db_20260519.sql

# Reiniciar stack completo
cd /opt/moodle-lms && docker compose restart

# Reconstruir si hay cambios en imagen
docker compose down && docker compose build && docker compose up -d
```

---

## 11. Matriz Comparativa de la Solución

### 11.1 Comparativa de Servidor Web

| Criterio | Apache (Elegido) | Nginx |
|---|---|---|
| Compatibilidad con Moodle | Alta (nativa) | Media |
| Soporte .htaccess | Nativo | Requiere conversión |
| Módulos PHP | mod_php nativo | Requiere PHP-FPM |
| Documentación Moodle | Extensa | Limitada |
| Rendimiento archivos estáticos | Medio | Alto |
| Memoria RAM | Mayor | Menor |
| **Veredicto** | **✅ Recomendado para Moodle** | Para proxy inverso |

### 11.2 Comparativa de Base de Datos

| Criterio | PostgreSQL (Elegido) | MariaDB | MySQL |
|---|---|---|---|
| Recomendado por Moodle | ✅ Sí (producción) | ✅ Sí | Parcial |
| Integridad referencial | Estricta | Alta | Media |
| MVCC (concurrencia) | Excelente | Buena | Buena |
| Soporte JSON nativo | ✅ Sí | Parcial | Parcial |
| Licencia | BSD | GPL | GPL/Comercial |
| Complejidad admin | Media | Baja | Baja |
| **Veredicto** | **✅ Producción** | Dev/Test | Legacy |

### 11.3 Comparativa de Despliegue

| Criterio | Docker (Elegido) | Bare Metal | VM Completa |
|---|---|---|---|
| Aislamiento de servicios | ✅ Excelente | No | Buena |
| Portabilidad | ✅ Total | No | Parcial |
| Reproducibilidad | ✅ Dockerfile | Manual | Manual |
| Overhead de recursos | Bajo (~5%) | Ninguno | Alto (~20%) |
| Facilidad de backup | ✅ Volúmenes | Complejo | Complejo |
| Escalabilidad | ✅ Alta | Limitada | Limitada |
| **Veredicto** | **✅ Moderno** | Simple | Aislamiento fuerte |

---

## 12. Conclusiones

1. **La arquitectura Docker con Apache + PostgreSQL es óptima** para el despliegue de Moodle en un entorno académico. Combina la modernidad de los contenedores con la compatibilidad nativa de Apache con Moodle.

2. **Moodle 5.x representa un avance arquitectónico** con la separación del directorio `public/` del directorio raíz, mejorando la seguridad al aislar archivos de configuración del DocumentRoot web.

3. **PostgreSQL ofrece la mayor solidez** para entornos de producción con Moodle, con soporte de 489 tablas creadas sin errores y excelente manejo de concurrencia.

4. **Docker simplifica enormemente** la administración, el respaldo y la migración de la plataforma, reduciendo la complejidad operacional comparado con instalaciones bare metal.

5. **La solución es reproducible** gracias al Dockerfile y docker-compose.yml que documentan exactamente el proceso de construcción y despliegue.

6. **Recomendación para producción**: Implementar HTTPS con certificados SSL, fail2ban para SSH, monitoreo con herramientas como Prometheus/Grafana y backups automatizados con retención de 30 días.

---

## 13. Referencias

1. Moodle Documentation. (2026). *Installation Guide*. https://docs.moodle.org/en/Installation
2. Moodle HQ. (2026). *Moodle 5.1 Release Notes*. https://moodledev.io/docs/5.1/devupdate
3. Docker Documentation. (2026). *Docker Compose Overview*. https://docs.docker.com/compose/
4. PostgreSQL Global Development Group. (2026). *PostgreSQL 15 Documentation*. https://www.postgresql.org/docs/15/
5. The Apache Software Foundation. (2026). *Apache HTTP Server Documentation*. https://httpd.apache.org/docs/
6. Fedora Project. (2026). *Fedora 43 Server Edition*. https://fedoraproject.org/server/
7. PHP Group. (2026). *PHP 8.2 Manual*. https://www.php.net/docs.php
