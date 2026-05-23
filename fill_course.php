<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');

$courseid  = 2;
$page_mod  = 15;
$forum_mod = 8;

// section number => mdl_course_sections.id
$secs = [1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6];

function add_cm_and_seq($DB, $courseid, $modid, $instance, $secid) {
    $cm = new stdClass();
    $cm->course     = $courseid;
    $cm->module     = $modid;
    $cm->instance   = $instance;
    $cm->section    = $secid;
    $cm->visible    = 1;
    $cm->added      = time();
    $cm->completion = 0;
    $cm->score      = 0;
    $cm->indent     = 0;
    $cm->groupmode  = 0;
    $cm->groupingid = 0;
    $cmid = $DB->insert_record('course_modules', $cm);
    $sec  = $DB->get_record('course_sections', ['id' => $secid]);
    $sec->sequence = trim(($sec->sequence ? $sec->sequence . ',' : '') . $cmid, ',');
    $DB->update_record('course_sections', $sec);
    return $cmid;
}

function add_page($DB, $courseid, $modid, $secid, $name, $content) {
    $p = new stdClass();
    $p->course          = $courseid;
    $p->name            = $name;
    $p->intro           = '';
    $p->introformat     = FORMAT_HTML;
    $p->content         = $content;
    $p->contentformat   = FORMAT_HTML;
    $p->legacyfiles     = 0;
    $p->legacyfileslast = null;
    $p->display         = 5;
    $p->displayoptions  = '';
    $p->revision        = 1;
    $p->timemodified    = time();
    $id = $DB->insert_record('page', $p);
    $cm = add_cm_and_seq($DB, $courseid, $modid, $id, $secid);
    echo "  [page] '$name' id=$id cm=$cm\n";
}

function add_forum($DB, $courseid, $modid, $secid, $name, $intro) {
    $f = new stdClass();
    $f->course          = $courseid;
    $f->type            = 'general';
    $f->name            = $name;
    $f->intro           = $intro;
    $f->introformat     = FORMAT_HTML;
    $f->assessed        = 0;
    $f->scale           = 0;
    $f->maxbytes        = 0;
    $f->maxattachments  = 9;
    $f->forcesubscribe  = 0;
    $f->trackingtype    = 1;
    $f->rsstype         = 0;
    $f->rssarticles     = 0;
    $f->timemodified    = time();
    $f->warnafter       = 0;
    $f->blockafter      = 0;
    $f->blockperiod     = 0;
    $f->completiondiscussions = 0;
    $f->completionreplies     = 0;
    $f->completionposts       = 0;
    $id = $DB->insert_record('forum', $f);
    $cm = add_cm_and_seq($DB, $courseid, $modid, $id, $secid);
    echo "  [forum] '$name' id=$id cm=$cm\n";
}

// ========== UNIDAD 1 - Servidores Linux ==========
echo "\n[Unidad 1 - Servidores Linux]\n";
$s = $secs[1];

add_page($DB, $courseid, $page_mod, $s,
'Instalacion y Configuracion de Fedora Server',
'<h2>Fedora Server - Instalacion y Primeros Pasos</h2>
<p>Fedora Server es una distribucion Linux de vanguardia orientada a servidores. Su ciclo de actualizacion rapido la hace ideal para aprender tecnologias modernas de infraestructura.</p>

<h3>Requisitos minimos</h3>
<ul>
  <li>CPU: x86_64, 2 nucleos o mas</li>
  <li>RAM: 2 GB (recomendado 4 GB)</li>
  <li>Disco: 20 GB minimo</li>
</ul>

<h3>Instalacion basica</h3>
<ol>
  <li>Descargar la ISO de <strong>getfedora.org</strong></li>
  <li>Crear USB booteable con Fedora Media Writer o Rufus</li>
  <li>Arrancar e iniciar el instalador Anaconda</li>
  <li>Configurar particionado, contraseña de root y hostname</li>
  <li>Reiniciar y actualizar el sistema</li>
</ol>

<h3>Configuracion post-instalacion</h3>
<pre><code># Actualizar todos los paquetes
dnf update -y

# Instalar utilidades esenciales
dnf install -y vim curl wget net-tools bash-completion htop

# Configurar hostname
hostnamectl set-hostname servidor01.infra.local

# Ver estado general del sistema
systemctl status
journalctl -xe | tail -20</code></pre>

<h3>Gestion de usuarios y grupos</h3>
<pre><code># Crear usuario con directorio home
useradd -m -s /bin/bash alumno01
passwd alumno01

# Agregar a grupo wheel (privilegios sudo)
usermod -aG wheel alumno01

# Crear grupo y asignar usuario
groupadd infraestructura
usermod -aG infraestructura alumno01

# Verificar grupos del usuario
id alumno01</code></pre>

<h3>Permisos en Linux</h3>
<pre><code># Notacion octal: rwx = 4+2+1
# 755 = rwxr-xr-x  (dueno: todo, grupo/otros: leer+ejecutar)
# 644 = rw-r--r--  (dueno: leer+escribir, grupo/otros: solo leer)

chmod 755 /var/www/html
chmod 644 /etc/nginx/nginx.conf
chown -R www-data:www-data /var/www/html

# Ver permisos de forma detallada
ls -la /var/www/html/</code></pre>');

add_page($DB, $courseid, $page_mod, $s,
'Comandos Esenciales de Linux',
'<h2>Referencia de Comandos Linux para Administracion de Servidores</h2>

<h3>Sistema de archivos</h3>
<pre><code>pwd                    # Mostrar directorio actual
ls -la                 # Listar con detalles y ocultos
cd /ruta/directorio    # Cambiar directorio
mkdir -p padre/hijo    # Crear directorios recursivos
cp -r origen/ destino/ # Copiar directorio recursivo
mv origen destino      # Mover o renombrar
rm -rf directorio/     # Eliminar directorio (irreversible)
find / -name "*.conf"  # Buscar archivos por nombre
locate nginx.conf      # Buscar en base de datos de archivos</code></pre>

<h3>Servicios con systemd</h3>
<pre><code>systemctl start   servicio   # Iniciar
systemctl stop    servicio   # Detener
systemctl restart servicio   # Reiniciar
systemctl reload  servicio   # Recargar configuracion sin bajar
systemctl enable  servicio   # Habilitar al arranque del sistema
systemctl disable servicio   # Deshabilitar del arranque
systemctl status  servicio   # Ver estado detallado
systemctl list-units --type=service --state=running</code></pre>

<h3>Procesos y recursos</h3>
<pre><code>top                    # Procesos en tiempo real
htop                   # Top interactivo mejorado
ps aux                 # Listado completo de procesos
ps aux | grep nginx    # Filtrar procesos
kill -9 PID            # Forzar terminacion de proceso
killall nginx          # Matar todos los procesos nginx
df -h                  # Uso de disco por particion
du -sh /var/*          # Espacio usado por directorio
free -h                # Memoria RAM y swap</code></pre>

<h3>Red y conectividad</h3>
<pre><code>ip addr show           # Ver interfaces de red y IPs
ip route show          # Ver tabla de enrutamiento
ss -tulnp              # Puertos TCP/UDP en escucha con PID
ping -c 4 8.8.8.8      # Prueba de conectividad
curl -I http://localhost    # Cabeceras HTTP del servidor local
curl -s http://api.local/v1 # Llamar API REST</code></pre>

<h3>SSH - Acceso remoto seguro</h3>
<pre><code># Conectar al servidor
ssh root@192.168.56.104

# Generar par de claves (autenticacion sin contrasena)
ssh-keygen -t rsa -b 4096 -C "mi@email.com"
ssh-copy-id usuario@servidor

# Copiar archivos de forma segura
scp archivo.txt usuario@servidor:/ruta/destino/
scp -r directorio/ usuario@servidor:/backup/

# Ejecutar comando remoto
ssh usuario@servidor "df -h && systemctl status httpd"</code></pre>

<h3>Hardening de SSH</h3>
<pre><code># /etc/ssh/sshd_config
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
ClientAliveInterval 300
AllowUsers administrador deployer</code></pre>');

add_page($DB, $courseid, $page_mod, $s,
'Gestion de Servicios y Logs con systemd',
'<h2>systemd - El sistema de init moderno en Linux</h2>
<p>systemd es el sistema de inicializacion y gestion de servicios presente en todas las distribuciones Linux modernas (Fedora, Ubuntu, Debian, RHEL, CentOS).</p>

<h3>Unidades de systemd</h3>
<ul>
  <li><strong>.service</strong>: Servicios del sistema (httpd, sshd, postgresql)</li>
  <li><strong>.timer</strong>: Tareas programadas (alternativa a cron)</li>
  <li><strong>.socket</strong>: Activacion por socket</li>
  <li><strong>.mount</strong>: Puntos de montaje</li>
</ul>

<h3>Crear un servicio personalizado</h3>
<pre><code># /etc/systemd/system/mi-app.service
[Unit]
Description=Mi Aplicacion Web
After=network.target postgresql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/mi-app
ExecStart=/usr/bin/node server.js
Restart=on-failure
RestartSec=5
Environment=NODE_ENV=production
Environment=PORT=3000

[Install]
WantedBy=multi-user.target</code></pre>

<pre><code># Activar el nuevo servicio
systemctl daemon-reload
systemctl enable --now mi-app
systemctl status mi-app</code></pre>

<h3>journald - Logs centralizados</h3>
<pre><code># Ver logs en tiempo real
journalctl -f

# Logs de servicio especifico
journalctl -u httpd -f

# Logs desde hace 1 hora
journalctl --since "1 hour ago"

# Solo errores y criticos
journalctl -p err --since "today"

# Logs del arranque actual
journalctl -b 0

# Logs del arranque anterior
journalctl -b -1

# Buscar texto en logs
journalctl -u nginx | grep "error"</code></pre>

<h3>timers de systemd (alternativa a cron)</h3>
<pre><code># /etc/systemd/system/backup.timer
[Unit]
Description=Backup diario a las 2 AM

[Timer]
OnCalendar=*-*-* 02:00:00
Persistent=true

[Install]
WantedBy=timers.target</code></pre>');

add_forum($DB, $courseid, $forum_mod, $s,
'Foro - Unidad 1: Servidores Linux',
'<p>Espacio de discusion para la Unidad 1. Comparte tus dudas sobre instalacion de Fedora, comandos Linux, gestion de usuarios, permisos y administracion de servicios.</p><p><strong>Temas sugeridos:</strong> problemas durante la instalacion, comandos que no entendiste, diferencias entre distribuciones, curiosidades sobre Linux.</p>');

// ========== UNIDAD 2 - Servicios Web ==========
echo "\n[Unidad 2 - Servicios Web]\n";
$s = $secs[2];

add_page($DB, $courseid, $page_mod, $s,
'Apache HTTP Server - Configuracion Completa',
'<h2>Apache HTTP Server 2.4</h2>
<p>Apache es el servidor web mas utilizado del mundo. Su arquitectura modular permite adaptarlo a casi cualquier caso de uso.</p>

<h3>Instalacion</h3>
<pre><code># Fedora / RHEL / CentOS
dnf install -y httpd
systemctl enable --now httpd

# Abrir puertos en el firewall
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --reload</code></pre>

<h3>Estructura de directorios</h3>
<pre><code>/etc/httpd/conf/httpd.conf      # Configuracion principal
/etc/httpd/conf.d/              # Configuraciones por sitio
/var/www/html/                  # DocumentRoot por defecto
/var/log/httpd/access_log       # Registro de accesos
/var/log/httpd/error_log        # Registro de errores</code></pre>

<h3>VirtualHost - Multiples sitios en un servidor</h3>
<pre><code># /etc/httpd/conf.d/sitio1.conf
&lt;VirtualHost *:80&gt;
    ServerName   sitio1.local
    ServerAlias  www.sitio1.local
    DocumentRoot /var/www/sitio1

    &lt;Directory /var/www/sitio1&gt;
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    &lt;/Directory&gt;

    ErrorLog  /var/log/httpd/sitio1-error.log
    CustomLog /var/log/httpd/sitio1-access.log combined
&lt;/VirtualHost&gt;</code></pre>

<h3>mod_rewrite - Reescritura de URLs</h3>
<pre><code># .htaccess en el DocumentRoot
RewriteEngine On
RewriteBase /

# Redirigir HTTP a HTTPS permanentemente
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# URLs amigables (frameworks PHP como Laravel)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?/$1 [L,QSA]</code></pre>

<h3>SSL/TLS con certificado propio</h3>
<pre><code># Generar certificado auto-firmado (desarrollo)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/servidor.key \
    -out    /etc/ssl/certs/servidor.crt \
    -subj "/C=CO/ST=Antioquia/O=InfraTec/CN=localhost"

# VirtualHost HTTPS
&lt;VirtualHost *:443&gt;
    ServerName mi-sitio.local
    DocumentRoot /var/www/html
    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/servidor.crt
    SSLCertificateKeyFile /etc/ssl/private/servidor.key
    SSLProtocol           TLSv1.2 TLSv1.3
&lt;/VirtualHost&gt;</code></pre>');

add_page($DB, $courseid, $page_mod, $s,
'Nginx - Servidor Web de Alto Rendimiento',
'<h2>Nginx</h2>
<p>Nginx es un servidor web y proxy inverso de alto rendimiento. Su modelo orientado a eventos le permite manejar miles de conexiones concurrentes con minimo consumo de memoria.</p>

<h3>Instalacion</h3>
<pre><code>dnf install -y nginx
systemctl enable --now nginx</code></pre>

<h3>Estructura de configuracion</h3>
<pre><code>/etc/nginx/nginx.conf           # Configuracion principal
/etc/nginx/conf.d/              # Sitios y configuraciones adicionales
/usr/share/nginx/html/          # DocumentRoot por defecto
/var/log/nginx/access.log       # Log de accesos
/var/log/nginx/error.log        # Log de errores</code></pre>

<h3>Servidor virtual basico (PHP-FPM)</h3>
<pre><code># /etc/nginx/conf.d/sitio1.conf
server {
    listen 80;
    server_name sitio1.local www.sitio1.local;
    root /var/www/sitio1;
    index index.html index.php;

    # URLs limpias para frameworks PHP
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Procesar PHP con PHP-FPM
    location ~ \.php$ {
        fastcgi_pass   unix:/run/php-fpm/www.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    # Denegar acceso a .htaccess y archivos ocultos
    location ~ /\. {
        deny all;
    }
}</code></pre>

<h3>Nginx como proxy inverso</h3>
<pre><code>server {
    listen 80;
    server_name api.local;

    location / {
        proxy_pass         http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade $http_upgrade;
        proxy_set_header   Connection "upgrade";
        proxy_set_header   Host $host;
        proxy_set_header   X-Real-IP $remote_addr;
        proxy_cache_bypass $http_upgrade;
    }
}</code></pre>

<h3>Apache vs Nginx - Comparativa</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse;">
<tr style="background:#f0f0f0"><th>Caracteristica</th><th>Apache</th><th>Nginx</th></tr>
<tr><td>Modelo de procesamiento</td><td>Prefork / Worker (hilos)</td><td>Event-driven (asincrono)</td></tr>
<tr><td>Configuracion dinamica</td><td>Si (.htaccess por directorio)</td><td>No (solo conf principal)</td></tr>
<tr><td>Contenido estatico</td><td>Bueno</td><td>Excelente</td></tr>
<tr><td>PHP integrado</td><td>mod_php directo</td><td>Requiere PHP-FPM</td></tr>
<tr><td>Proxy inverso</td><td>mod_proxy</td><td>Nativo y mas eficiente</td></tr>
<tr><td>Uso de memoria</td><td>Mayor bajo carga</td><td>Menor y mas predecible</td></tr>
</table>');

add_page($DB, $courseid, $page_mod, $s,
'PHP-FPM y Optimizacion de Rendimiento Web',
'<h2>PHP-FPM y Ajuste de Rendimiento</h2>

<h3>PHP-FPM (FastCGI Process Manager)</h3>
<p>PHP-FPM es la implementacion de FastCGI para PHP. Gestiona un pool de procesos PHP y los reutiliza entre solicitudes, siendo mucho mas eficiente que el modelo CGI clasico.</p>

<pre><code># Instalar PHP-FPM
dnf install -y php php-fpm php-pgsql php-mysqlnd php-mbstring php-gd

systemctl enable --now php-fpm</code></pre>

<h3>Configuracion del pool (/etc/php-fpm.d/www.conf)</h3>
<pre><code>[www]
user  = apache
group = apache

; Socket UNIX (mas rapido que TCP en local)
listen = /run/php-fpm/www.sock
listen.owner = nginx
listen.group = nginx

; Gestion dinamica de procesos
pm = dynamic
pm.max_children      = 50
pm.start_servers     = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests      = 500</code></pre>

<h3>Optimizacion de PHP (/etc/php.ini)</h3>
<pre><code>; Tiempo maximo de ejecucion de un script
max_execution_time = 60

; Memoria por proceso PHP
memory_limit = 256M

; Tamano maximo de upload
upload_max_filesize = 50M
post_max_size = 55M

; Variables de entrada (formularios grandes)
max_input_vars = 3000

; OPcache - Cache de bytecode PHP (mejora rendimiento hasta 3x)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2</code></pre>

<h3>Cabeceras de seguridad HTTP</h3>
<pre><code># En Apache: /etc/httpd/conf.d/security.conf
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=31536000"

# En Nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Strict-Transport-Security "max-age=31536000" always;</code></pre>');

add_forum($DB, $courseid, $forum_mod, $s,
'Foro - Unidad 2: Servicios Web',
'<p>Discusion sobre Apache, Nginx, Virtual Hosts, SSL/TLS, PHP-FPM y configuracion de servidores web.</p><p><strong>Temas sugeridos:</strong> diferencias entre Apache y Nginx, problemas con VirtualHosts, configuracion de HTTPS, mod_rewrite, rendimiento web.</p>');

// ========== UNIDAD 3 - Bases de Datos ==========
echo "\n[Unidad 3 - Bases de Datos]\n";
$s = $secs[3];

add_page($DB, $courseid, $page_mod, $s,
'PostgreSQL - Instalacion, Administracion y SQL',
'<h2>PostgreSQL</h2>
<p>PostgreSQL es el sistema de gestion de bases de datos relacionales open source mas avanzado. Soporta transacciones ACID, tipos de datos complejos (JSON, arrays, geometria), y se usa en produccion en empresas como Instagram, Spotify y GitHub.</p>

<h3>Instalacion en Fedora</h3>
<pre><code>dnf install -y postgresql-server postgresql-contrib
postgresql-setup --initdb
systemctl enable --now postgresql

# Primer acceso (usuario del SO postgres)
sudo -u postgres psql</code></pre>

<h3>Configuracion de acceso remoto</h3>
<pre><code># /var/lib/pgsql/data/postgresql.conf
listen_addresses = \'*\'   # Escuchar en todas las interfaces

# /var/lib/pgsql/data/pg_hba.conf
# Tipo  BD       Usuario  Origen           Metodo
host    all      all      192.168.0.0/24   md5</code></pre>

<h3>Administracion de usuarios y bases de datos</h3>
<pre><code>-- Dentro de psql (como postgres)

-- Crear base de datos
CREATE DATABASE appdb ENCODING = UTF8;

-- Crear usuario con contrasena
CREATE USER appuser WITH ENCRYPTED PASSWORD SecurePass123;

-- Dar permisos completos sobre la BD
GRANT ALL PRIVILEGES ON DATABASE appdb TO appuser;

-- Conectar a la base de datos
\c appdb

-- Dar permisos sobre el schema
GRANT ALL ON SCHEMA public TO appuser;</code></pre>

<h3>Comandos psql esenciales</h3>
<pre><code>\l              -- Listar bases de datos
\c nombre_bd    -- Conectar a una BD
\dt             -- Listar tablas
\d tabla        -- Describir estructura de tabla
\du             -- Listar roles/usuarios
\timing         -- Medir tiempo de consultas
\q              -- Salir</code></pre>

<h3>SQL fundamental</h3>
<pre><code>-- Crear tabla con tipos modernos de PostgreSQL
CREATE TABLE productos (
    id         SERIAL PRIMARY KEY,
    nombre     VARCHAR(200) NOT NULL,
    precio     NUMERIC(10,2) CHECK (precio >= 0),
    stock      INTEGER DEFAULT 0,
    activo     BOOLEAN DEFAULT TRUE,
    metadata   JSONB,
    creado_en  TIMESTAMP DEFAULT NOW()
);

-- Insertar
INSERT INTO productos (nombre, precio, stock)
VALUES (Servidor Dell R740, 4500.00, 3);

-- Consultar con filtros
SELECT nombre, precio
FROM productos
WHERE activo = true AND precio BETWEEN 100 AND 5000
ORDER BY precio DESC
LIMIT 10;

-- Actualizar
UPDATE productos SET stock = stock - 1 WHERE id = 1;

-- Eliminar con condicion
DELETE FROM productos WHERE activo = false;</code></pre>');

add_page($DB, $courseid, $page_mod, $s,
'MySQL y MariaDB - Administracion Practica',
'<h2>MySQL / MariaDB</h2>
<p>MySQL es el motor de base de datos open source mas popular del mundo, especialmente en aplicaciones web (LAMP/LEMP). MariaDB es su fork compatible y activamente desarrollado por la comunidad.</p>

<h3>Instalacion de MariaDB en Fedora</h3>
<pre><code>dnf install -y mariadb-server mariadb
systemctl enable --now mariadb

# Asegurar la instalacion (configurar root, eliminar anonimos)
mysql_secure_installation</code></pre>

<h3>Gestion de usuarios y permisos</h3>
<pre><code>-- Entrar como root
mysql -u root -p

-- Crear base de datos con charset correcto
CREATE DATABASE appdb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Crear usuario solo para esa BD (principio de minimo privilegio)
CREATE USER appuser@localhost IDENTIFIED BY SecurePass123;
GRANT ALL PRIVILEGES ON appdb.* TO appuser@localhost;
FLUSH PRIVILEGES;

-- Ver permisos de un usuario
SHOW GRANTS FOR appuser@localhost;</code></pre>

<h3>Backup con mysqldump</h3>
<pre><code># Backup de una base de datos
mysqldump -u root -p appdb > backup_appdb_$(date +%Y%m%d).sql

# Backup de todas las bases de datos
mysqldump -u root -p --all-databases > backup_all_$(date +%Y%m%d).sql

# Backup comprimido
mysqldump -u root -p appdb | gzip > backup_appdb_$(date +%Y%m%d).sql.gz

# Restaurar
mysql -u root -p appdb < backup_appdb_20250519.sql</code></pre>

<h3>Optimizacion basica</h3>
<pre><code># /etc/my.cnf.d/server.cnf
[mysqld]
innodb_buffer_pool_size = 1G    # 50-80% de la RAM disponible
innodb_log_file_size    = 256M
max_connections         = 200
query_cache_type        = 0     # Desactivado en MySQL 8+
slow_query_log          = 1
slow_query_log_file     = /var/log/mariadb/slow.log
long_query_time         = 2     # Consultas de mas de 2 segundos</code></pre>');

add_page($DB, $courseid, $page_mod, $s,
'Backup, Restore y Seguridad en Bases de Datos',
'<h2>Estrategias de Respaldo en Bases de Datos</h2>

<h3>Regla 3-2-1 de Backups</h3>
<ul>
<li><strong>3</strong> copias de los datos</li>
<li><strong>2</strong> en diferentes tipos de almacenamiento (disco local + nube)</li>
<li><strong>1</strong> fuera del sitio fisico (offsite)</li>
</ul>

<h3>Tipos de backup</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse">
<tr style="background:#f0f0f0"><th>Tipo</th><th>Descripcion</th><th>Ventaja</th><th>Desventaja</th></tr>
<tr><td>Completo</td><td>Copia toda la BD</td><td>Recuperacion simple</td><td>Lento, ocupa mucho espacio</td></tr>
<tr><td>Incremental</td><td>Solo cambios desde ultimo backup</td><td>Rapido, compacto</td><td>Recuperacion compleja</td></tr>
<tr><td>Diferencial</td><td>Cambios desde ultimo completo</td><td>Balance velocidad/espacio</td><td>Crece con el tiempo</td></tr>
</table>

<h3>PostgreSQL - pg_dump y pg_restore</h3>
<pre><code># Backup en formato comprimido
pg_dump -U postgres -F c -f /backup/mi_bd_$(date +%Y%m%d).dump mi_bd

# Backup SQL plano
pg_dump -U postgres mi_bd > /backup/mi_bd.sql

# Backup de todo el cluster
pg_dumpall -U postgres > /backup/cluster_$(date +%Y%m%d).sql

# Restaurar formato comprimido
pg_restore -U postgres -d mi_bd -c /backup/mi_bd_20250519.dump

# Restaurar SQL plano
psql -U postgres mi_bd < /backup/mi_bd.sql</code></pre>

<h3>Script de backup automatizado</h3>
<pre><code>#!/bin/bash
# /usr/local/bin/backup_postgres.sh
BACKUP_DIR=/var/backups/postgres
FECHA=$(date +%Y%m%d_%H%M)
RETENER_DIAS=7

mkdir -p $BACKUP_DIR

for DB in $(psql -U postgres -t -c "SELECT datname FROM pg_database WHERE NOT datistemplate;"); do
    pg_dump -U postgres -F c $DB | gzip > $BACKUP_DIR/${DB}_${FECHA}.dump.gz
    echo "[OK] Backup de $DB completado"
done

# Limpiar backups antiguos
find $BACKUP_DIR -name "*.dump.gz" -mtime +$RETENER_DIAS -delete
echo "[OK] Backups de mas de $RETENER_DIAS dias eliminados"</code></pre>

<h3>Seguridad en bases de datos</h3>
<ul>
<li>Principio de minimo privilegio: un usuario por aplicacion, solo con lo que necesita</li>
<li>Nunca usar <code>root</code> o <code>postgres</code> para conexiones de aplicaciones</li>
<li>Cifrar conexiones con SSL/TLS (<code>sslmode=require</code> en PostgreSQL)</li>
<li>Cambiar el puerto por defecto si es posible</li>
<li>Mantener actualizaciones de seguridad al dia</li>
<li>Auditar intentos de acceso fallidos</li>
</ul>');

add_forum($DB, $courseid, $forum_mod, $s,
'Foro - Unidad 3: Bases de Datos',
'<p>Discusion sobre PostgreSQL, MySQL, MariaDB, diseno de esquemas, consultas SQL, backup y seguridad en bases de datos.</p><p><strong>Temas sugeridos:</strong> diferencias entre motores, cuando usar PostgreSQL vs MySQL, optimizacion de consultas, estrategias de backup en produccion.</p>');

// ========== UNIDAD 4 - Docker ==========
echo "\n[Unidad 4 - Contenedores Docker]\n";
$s = $secs[4];

add_page($DB, $courseid, $page_mod, $s,
'Docker - Conceptos Fundamentales y CLI',
'<h2>Contenedores con Docker</h2>
<p>Docker es una plataforma de contenedorizacion que permite empaquetar aplicaciones y sus dependencias en unidades portables y aisladas. Los contenedores comparten el kernel del sistema operativo, siendo mas ligeros y rapidos que las maquinas virtuales.</p>

<h3>Conceptos clave</h3>
<ul>
<li><strong>Imagen:</strong> Plantilla inmutable que define el contenido del contenedor (sistema base + aplicacion + dependencias)</li>
<li><strong>Contenedor:</strong> Instancia en ejecucion de una imagen. Puede iniciarse, detenerse y eliminarse</li>
<li><strong>Registry:</strong> Repositorio de imagenes. El publico principal es <strong>Docker Hub</strong></li>
<li><strong>Volumen:</strong> Almacenamiento persistente que sobrevive al ciclo de vida del contenedor</li>
<li><strong>Red:</strong> Aislamiento y comunicacion entre contenedores</li>
</ul>

<h3>Instalacion en Fedora</h3>
<pre><code>dnf install -y docker
systemctl enable --now docker
usermod -aG docker $USER   # Usar Docker sin sudo (requiere re-login)</code></pre>

<h3>Ciclo de vida de un contenedor</h3>
<pre><code># Descargar imagen desde Docker Hub
docker pull nginx:1.25

# Ejecutar contenedor en background (-d) con nombre y puerto
docker run -d --name mi-nginx -p 8080:80 nginx:1.25

# Ver contenedores activos
docker ps

# Ver todos los contenedores (incluidos detenidos)
docker ps -a

# Ver logs en tiempo real
docker logs -f mi-nginx

# Entrar al contenedor interactivamente
docker exec -it mi-nginx bash

# Detener contenedor
docker stop mi-nginx

# Iniciar contenedor detenido
docker start mi-nginx

# Eliminar contenedor (debe estar detenido)
docker rm mi-nginx

# Eliminar imagen
docker rmi nginx:1.25</code></pre>

<h3>Volumenes - Persistencia de datos</h3>
<pre><code># Crear volumen nombrado
docker volume create datos-postgres

# Usar volumen en contenedor
docker run -d \
  --name mi-postgres \
  -e POSTGRES_PASSWORD=secreto \
  -v datos-postgres:/var/lib/postgresql/data \
  postgres:15

# Montar directorio del host (bind mount)
docker run -d -v /var/www/html:/var/www/html nginx

# Listar y eliminar volumenes
docker volume ls
docker volume rm datos-postgres</code></pre>

<h3>Redes Docker</h3>
<pre><code># Crear red personalizada
docker network create mi-red

# Conectar contenedores a la misma red
docker run -d --name db    --network mi-red postgres:15
docker run -d --name web   --network mi-red -p 80:80 nginx
# Desde "web" puede resolver "db" por nombre DNS

# Ver redes
docker network ls
docker network inspect mi-red</code></pre>');

add_page($DB, $courseid, $page_mod, $s,
'Dockerfile - Construccion de Imagenes Personalizadas',
'<h2>Crear Imagenes con Dockerfile</h2>
<p>Un Dockerfile es un archivo de instrucciones que define como construir una imagen Docker personalizada. Cada instruccion genera una capa inmutable en la imagen.</p>

<h3>Instrucciones principales</h3>
<pre><code># Imagen base (usar etiqueta especifica, no "latest" en produccion)
FROM php:8.2-apache

# Metadatos
LABEL maintainer="admin@empresa.com"
LABEL version="1.0"

# Ejecutar comandos durante la construccion de la imagen
# Combinar con && para minimizar capas
RUN apt-get update && apt-get install -y \
    curl libpq-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_pgsql zip gd

# Copiar archivos al contexto del contenedor
COPY ./src/ /var/www/html/
COPY ./config/apache.conf /etc/apache2/sites-enabled/000-default.conf

# Variables de entorno disponibles en tiempo de ejecucion
ENV APP_ENV=production \
    DB_PORT=5432

# Directorio de trabajo para comandos siguientes
WORKDIR /var/www/html

# Puerto que expone el contenedor (solo documentacion)
EXPOSE 80

# Volumen para datos persistentes
VOLUME ["/var/uploads"]

# Script de inicializacion como punto de entrada
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

# Comando por defecto (puede sobreescribirse)
CMD ["apache2-foreground"]</code></pre>

<h3>Construir y publicar imagenes</h3>
<pre><code># Construir imagen etiquetada
docker build -t mi-app:1.0.0 .

# Construir sin cache (tras cambios en RUN)
docker build --no-cache -t mi-app:latest .

# Etiquetar para Docker Hub
docker tag mi-app:1.0.0 miusuario/mi-app:1.0.0

# Publicar en Docker Hub
docker login
docker push miusuario/mi-app:1.0.0</code></pre>

<h3>.dockerignore - Excluir archivos del contexto</h3>
<pre><code># .dockerignore
.git
.env
*.log
node_modules/
tests/
README.md
docker-compose*.yml</code></pre>

<h3>Buenas practicas</h3>
<ul>
<li>Usar imagen base oficial y con etiqueta especifica</li>
<li>Combinar RUN con <code>&amp;&amp;</code> y limpiar cache en la misma capa</li>
<li>Ordenar las instrucciones de menos a mas cambiantes (aprovechar cache)</li>
<li>Un proceso principal por contenedor</li>
<li>No incluir secretos en la imagen (usar variables de entorno o secrets)</li>
<li>Mantener las imagenes lo mas pequenas posible</li>
</ul>');

add_page($DB, $courseid, $page_mod, $s,
'Docker Compose - Orquestacion Multi-Contenedor',
'<h2>Docker Compose</h2>
<p>Docker Compose permite definir y gestionar aplicaciones compuestas por multiples contenedores mediante un archivo <code>docker-compose.yml</code>. Es la herramienta estandar para entornos de desarrollo y despliegues en un solo servidor.</p>

<h3>Estructura del docker-compose.yml</h3>
<pre><code>services:

  # Base de datos PostgreSQL
  db:
    image: postgres:15
    container_name: app-db
    environment:
      POSTGRES_DB:       appdb
      POSTGRES_USER:     appuser
      POSTGRES_PASSWORD: SecurePass2024
    volumes:
      - pgdata:/var/lib/postgresql/data
    networks:
      - app-net
    restart: unless-stopped
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U appuser -d appdb"]
      interval: 10s
      timeout: 5s
      retries: 10

  # Aplicacion web
  web:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: app-web
    ports:
      - "80:80"
    environment:
      DB_HOST: db
      DB_NAME: appdb
      DB_USER: appuser
    volumes:
      - uploads:/var/uploads
    depends_on:
      db:
        condition: service_healthy
    networks:
      - app-net
    restart: unless-stopped

volumes:
  pgdata:
    driver: local
  uploads:
    driver: local

networks:
  app-net:
    driver: bridge</code></pre>

<h3>Comandos esenciales de Compose</h3>
<pre><code># Levantar servicios en background (construye si es necesario)
docker compose up -d --build

# Ver estado de los servicios
docker compose ps

# Ver logs de todos los servicios
docker compose logs -f

# Ver logs de un servicio especifico
docker compose logs -f web

# Detener servicios (preserva volumenes y redes)
docker compose down

# Detener y ELIMINAR volumenes (cuidado: borra datos)
docker compose down -v

# Reconstruir una imagen especifica
docker compose build web

# Ejecutar comando en servicio activo
docker compose exec web bash
docker compose exec db psql -U appuser appdb</code></pre>

<h3>Variables de entorno con .env</h3>
<pre><code># .env (NO subir a git)
DB_PASSWORD=SuperSecreta2024
ADMIN_PASS=Admin@Seguro

# docker-compose.yml usa las variables automaticamente
environment:
  POSTGRES_PASSWORD: ${DB_PASSWORD}</code></pre>');

add_forum($DB, $courseid, $forum_mod, $s,
'Foro - Unidad 4: Docker y Contenedores',
'<p>Discusion sobre Docker, construccion de imagenes, volumenes, redes y Docker Compose. Comparte tu experiencia desplegando aplicaciones con contenedores.</p><p><strong>Temas sugeridos:</strong> diferencias entre VM y contenedores, como depurar contenedores, estrategias de persistencia de datos, casos de uso reales de Docker Compose.</p>');

// ========== UNIDAD 5 - Seguridad y Respaldo ==========
echo "\n[Unidad 5 - Seguridad y Respaldo]\n";
$s = $secs[5];

add_page($DB, $courseid, $page_mod, $s,
'Seguridad en Infraestructuras Linux',
'<h2>Hardening y Seguridad en Servidores Linux</h2>

<h3>Principios fundamentales de seguridad</h3>
<ul>
<li><strong>Minimo privilegio:</strong> Cada usuario y proceso tiene solo los permisos estrictamente necesarios</li>
<li><strong>Defensa en profundidad:</strong> Multiples capas de seguridad (firewall + autenticacion + cifrado + monitoreo)</li>
<li><strong>Superficie de ataque minima:</strong> Desinstalar o deshabilitar todo lo que no se use</li>
<li><strong>Asumir compromiso:</strong> Disenar los sistemas asumiendo que seran atacados</li>
</ul>

<h3>firewalld - Cortafuegos en Fedora/RHEL</h3>
<pre><code># Ver estado y zonas activas
firewall-cmd --state
firewall-cmd --get-active-zones

# Permitir servicios por nombre
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-service=ssh

# Permitir puerto especifico
firewall-cmd --permanent --add-port=8080/tcp

# Bloquear IP especifica
firewall-cmd --permanent --add-rich-rule="rule family=ipv4 source address=192.168.1.50 reject"

# Aplicar cambios permanentes
firewall-cmd --reload

# Ver todas las reglas activas
firewall-cmd --list-all</code></pre>

<h3>fail2ban - Proteccion contra fuerza bruta</h3>
<pre><code>dnf install -y fail2ban
systemctl enable --now fail2ban

# /etc/fail2ban/jail.local
[DEFAULT]
bantime  = 3600    # Banear por 1 hora
findtime = 600     # Ventana de 10 minutos
maxretry = 5       # 5 intentos fallidos

[sshd]
enabled  = true
port     = ssh
logpath  = %(sshd_log)s

[apache-auth]
enabled  = true
logpath  = /var/log/httpd/error_log</code></pre>

<h3>SELinux - Control de Acceso Obligatorio</h3>
<pre><code># Ver modo actual
getenforce   # Enforcing | Permissive | Disabled

# Ver contexto SELinux de archivos
ls -Z /var/www/html/

# Corregir contexto (despues de copiar archivos)
restorecon -Rv /var/www/html/

# Permitir httpd conectarse a red (para PHP reverse proxy)
setsebool -P httpd_can_network_connect 1</code></pre>

<h3>Actualizaciones automaticas de seguridad</h3>
<pre><code>dnf install -y dnf-automatic

# /etc/dnf/automatic.conf
apply_updates = yes   # Aplicar automaticamente

systemctl enable --now dnf-automatic.timer</code></pre>');

add_page($DB, $courseid, $page_mod, $s,
'Monitoreo de Infraestructura y Logs',
'<h2>Monitoreo de Servidores Linux</h2>
<p>Monitorear la infraestructura permite detectar problemas antes de que afecten a los usuarios, planificar la capacidad y responder rapidamente ante incidentes.</p>

<h3>Herramientas nativas de diagnostico</h3>
<pre><code># Carga del sistema y CPU
top              # Tiempo real, interactivo
htop             # top mejorado con graficas
mpstat 1 5       # Estadisticas CPU cada 1 seg, 5 veces
uptime           # Carga promedio del ultimo 1, 5, 15 min

# Memoria
free -h          # RAM y swap disponibles
vmstat 1         # Memoria virtual, swap, I/O en tiempo real

# Disco
df -h            # Espacio por particion
du -sh /var/*    # Espacio por directorio (ordenar: | sort -h)
iostat -x 1      # Rendimiento de I/O por dispositivo

# Red
ss -tulnp        # Sockets activos con nombre de proceso
iftop            # Trafico de red en tiempo real
tcpdump -i eth0 port 80   # Captura de paquetes HTTP</code></pre>

<h3>Logs del sistema con journalctl</h3>
<pre><code># Seguir logs en tiempo real
journalctl -f

# Logs de un servicio especifico
journalctl -u postgresql -f

# Solo errores de las ultimas 2 horas
journalctl -p err --since "2 hours ago"

# Logs del ultimo arranque
journalctl -b 0

# Logs del arranque anterior (util tras un crash)
journalctl -b -1

# Exportar logs a archivo para analisis
journalctl -u httpd --since "2025-05-01" > /tmp/httpd-mayo.log</code></pre>

<h3>Script de reporte automatico</h3>
<pre><code>#!/bin/bash
# /usr/local/bin/daily_report.sh
HOST=$(hostname)
DATE=$(date "+%Y-%m-%d %H:%M")
REPORT="/var/log/daily_report_$(date +%Y%m%d).txt"

{
echo "===== Reporte diario: $HOST - $DATE ====="
echo ""
echo "--- Uso de CPU (top 5 procesos) ---"
ps aux --sort=-%cpu | head -6

echo ""
echo "--- Memoria ---"
free -h

echo ""
echo "--- Disco ---"
df -h | grep -v tmpfs | grep -v udev

echo ""
echo "--- Ultimos errores del sistema ---"
journalctl -p err --since "24 hours ago" --no-pager | tail -15

echo ""
echo "--- Intentos SSH fallidos ---"
journalctl -u sshd --since "24 hours ago" | grep "Failed" | tail -10
} > $REPORT

# Enviar por correo (si hay mailx configurado)
# mail -s "Reporte $HOST - $DATE" admin@empresa.com < $REPORT</code></pre>');

add_page($DB, $courseid, $page_mod, $s,
'Estrategias de Backup y Recuperacion ante Desastres',
'<h2>Backup y Disaster Recovery</h2>

<h3>Regla 3-2-1</h3>
<p>El estandar de la industria para backups: <strong>3</strong> copias de los datos, en <strong>2</strong> medios/ubicaciones diferentes, con <strong>1</strong> copia fuera del sitio (offsite: nube, otra ubicacion fisica).</p>

<h3>rsync - Sincronizacion eficiente</h3>
<pre><code># Sincronizar directorio local
rsync -avz --delete /var/www/html/ /backup/www/

# Sincronizar con servidor remoto
rsync -avz -e ssh /var/www/html/ backup@servidor-remoto:/backup/web/

# Backup incremental con hard links (ahorra espacio)
rsync -avz --link-dest=/backup/$(date -d yesterday +%Y%m%d) \
    /var/www/html/ /backup/$(date +%Y%m%d)/</code></pre>

<h3>tar - Archivado y compresion</h3>
<pre><code># Crear backup comprimido con timestamp
tar -czf /backup/web_$(date +%Y%m%d_%H%M).tar.gz \
    --exclude="*.log" --exclude=".git" \
    /var/www/html/

# Verificar integridad del backup
tar -tzf /backup/web_20250519_0200.tar.gz | head -20

# Restaurar en directorio especifico
tar -xzf /backup/web_20250519_0200.tar.gz -C /restore/</code></pre>

<h3>Script de backup completo con rotacion</h3>
<pre><code">#!/bin/bash
# /usr/local/bin/backup_completo.sh
BACKUP_BASE=/var/backups
FECHA=$(date +%Y%m%d_%H%M)
RETENER=7  # dias

mkdir -p $BACKUP_BASE/{web,db,config}

# Web
tar -czf $BACKUP_BASE/web/web_$FECHA.tar.gz /var/www/html/
echo "[OK] Backup web: web_$FECHA.tar.gz"

# Base de datos PostgreSQL
pg_dumpall -U postgres | gzip > $BACKUP_BASE/db/db_$FECHA.sql.gz
echo "[OK] Backup BD: db_$FECHA.sql.gz"

# Configuraciones del sistema
tar -czf $BACKUP_BASE/config/etc_$FECHA.tar.gz \
    /etc/httpd/ /etc/nginx/ /etc/postgresql/ /etc/ssh/
echo "[OK] Backup config: etc_$FECHA.tar.gz"

# Rotacion: eliminar backups mas viejos que RETENER dias
find $BACKUP_BASE -name "*.tar.gz" -mtime +$RETENER -delete
find $BACKUP_BASE -name "*.sql.gz" -mtime +$RETENER -delete
echo "[OK] Rotacion completada (retener $RETENER dias)"</code></pre>

<h3>Automatizacion con cron</h3>
<pre><code"># crontab -e (como root)
# Backup completo todos los dias a las 02:00
0 2 * * * /usr/local/bin/backup_completo.sh >> /var/log/backup.log 2>&1

# Verificar espacio en disco cada hora
0 * * * * df -h | awk NR>1{if($5+0>85) print "ALERTA: "$6" al "$5} | mail -s "Disco lleno" admin@empresa.com</code></pre>

<h3>Plan de Recuperacion ante Desastres (DRP)</h3>
<ol>
<li><strong>RTO</strong> (Recovery Time Objective): Tiempo maximo tolerable de inactividad</li>
<li><strong>RPO</strong> (Recovery Point Objective): Maxima perdida de datos tolerable</li>
<li>Documentar paso a paso el procedimiento de recuperacion</li>
<li>Probar los backups regularmente (restaurar en entorno de prueba)</li>
<li>Mantener runbook actualizado con contactos y procedimientos</li>
</ol>');

add_forum($DB, $courseid, $forum_mod, $s,
'Foro - Unidad 5: Seguridad y Respaldo',
'<p>Discusion sobre hardening de servidores, firewall, monitoreo, estrategias de backup y planes de recuperacion ante desastres.</p><p><strong>Temas sugeridos:</strong> configurar fail2ban en la practica, experiencias con SELinux, herramientas de monitoreo open source (Prometheus, Grafana, Zabbix), como probar un plan de recuperacion.</p>');

// Reconstruir cache del curso
rebuild_course_cache($courseid, true);
echo "\n==> Cache del curso reconstruida.\n";
echo "==> COMPLETADO. Resumen:\n";
echo "    - Unidad 1: 3 paginas + 1 foro\n";
echo "    - Unidad 2: 3 paginas + 1 foro\n";
echo "    - Unidad 3: 3 paginas + 1 foro\n";
echo "    - Unidad 4: 3 paginas + 1 foro\n";
echo "    - Unidad 5: 3 paginas + 1 foro\n";
echo "    Total: 15 paginas de contenido + 5 foros de discusion\n";
