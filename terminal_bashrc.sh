
# Aliases útiles
alias ll='ls -la'
alias ports='ss -tulnp'
alias disk='df -h'
alias mem='free -h'
alias procs='ps aux | head -20'

# Prompt con color
PS1='\[\033[01;32m\]alumno@eva-terminal\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ '

# Bienvenida al abrir terminal
clear
echo ""
echo "  +----------------------------------------------------------+"
echo "  |   Terminal de Practica -- Infraestructura Tecnologica   |"
echo "  |   Escribe  help-infra       para comandos por unidad    |"
echo "  |   Escribe  help-infra 4     para comandos Docker        |"
echo "  +----------------------------------------------------------+"
echo ""

# Cheat-sheet interactivo por unidad
help-infra() {
  local u=${1:-all}
  case $u in
    1|linux)
      echo ""
      echo "=== Unidad 1 - Servidores Linux ==="
      echo "  ls -la / pwd / cd / mkdir -p / cp -r / mv / rm -rf"
      echo "  chmod 755 archivo        chown user:grupo archivo"
      echo "  useradd -m prueba        passwd prueba"
      echo "  ps aux                   kill -9 PID"
      echo "  df -h                    free -h"
      echo "  ip addr show             ss -tulnp"
      echo "  tail -f /var/log/syslog"
      ;;
    2|web)
      echo ""
      echo "=== Unidad 2 - Servicios Web ==="
      echo "  sudo service apache2 start|stop|status"
      echo "  sudo service nginx   start|stop|status"
      echo "  curl -I http://localhost"
      echo "  curl -I http://moodle-app"
      echo "  cat /etc/apache2/sites-enabled/000-default.conf"
      echo "  cat /etc/nginx/sites-enabled/default"
      echo "  openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout k.key -out c.crt -subj '/CN=localhost'"
      ;;
    3|db)
      echo ""
      echo "=== Unidad 3 - Bases de Datos (PostgreSQL) ==="
      echo "  psql -h moodle-db -U moodle_user -d moodle_db"
      echo "  Contrasena: Moodle@Secure2024"
      echo ""
      echo "  Dentro de psql:"
      echo "    \\l            listar bases de datos"
      echo "    \\dt           listar tablas"
      echo "    \\d mdl_user   describir tabla"
      echo "    SELECT username, email FROM mdl_user WHERE deleted=0;"
      echo "    SELECT fullname FROM mdl_course WHERE id=2;"
      echo "    \\q            salir"
      echo ""
      echo "  Backup:"
      echo "    pg_dump -h moodle-db -U moodle_user moodle_db > backup.sql"
      ;;
    4|docker)
      echo ""
      echo "=== Unidad 4 - Docker ==="
      echo "  docker ps                        contenedores activos"
      echo "  docker ps -a                     todos los contenedores"
      echo "  docker images                    imagenes locales"
      echo "  docker logs moodle-app           logs de Moodle"
      echo "  docker logs moodle-db            logs de PostgreSQL"
      echo "  docker exec -it moodle-app bash  entrar al contenedor Moodle"
      echo "  docker exec -it moodle-db bash   entrar al contenedor BD"
      echo "  docker stats                     uso de CPU/RAM en tiempo real"
      echo "  docker network ls                redes Docker"
      echo "  docker network inspect moodle-lms_moodle-network"
      echo "  docker volume ls                 volumenes persistentes"
      echo "  docker inspect moodle-app        detalles del contenedor"
      ;;
    5|sec)
      echo ""
      echo "=== Unidad 5 - Seguridad y Respaldo ==="
      echo "  sudo iptables -L -n -v           ver reglas de firewall"
      echo "  sudo ufw status                  estado UFW"
      echo "  ssh-keygen -t rsa -b 4096 -C 'test@eva'"
      echo "  openssl rand -hex 32             generar secreto aleatorio"
      echo "  openssl s_client -connect moodle-app:443"
      echo "  curl -s http://moodle-app | head -20"
      echo ""
      echo "  Backup con tar:"
      echo "    tar -czf backup_\$(date +%Y%m%d).tar.gz /etc/nginx/"
      echo "    tar -tzf backup_*.tar.gz"
      ;;
    *)
      echo ""
      echo "Uso: help-infra [unidad]"
      echo "  help-infra 1   Unidad 1 - Servidores Linux"
      echo "  help-infra 2   Unidad 2 - Servicios Web"
      echo "  help-infra 3   Unidad 3 - Bases de Datos"
      echo "  help-infra 4   Unidad 4 - Docker"
      echo "  help-infra 5   Unidad 5 - Seguridad"
      echo ""
      echo "Alias disponibles:  ll  ports  disk  mem  procs"
      ;;
  esac
  echo ""
}
export -f help-infra
