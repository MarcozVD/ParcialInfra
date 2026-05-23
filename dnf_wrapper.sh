#!/bin/bash
# Wrapper: dnf → apt-get (con traducción de nombres de paquetes Fedora → Ubuntu)

# Tabla de traducción de paquetes
translate_pkg() {
  local pkg="$1"
  case "$pkg" in
    postgresql-server)       echo "postgresql" ;;
    postgresql-contrib)      echo "postgresql-contrib" ;;
    postgresql)              echo "postgresql" ;;
    httpd)                   echo "apache2" ;;
    httpd-tools)             echo "apache2-utils" ;;
    mariadb-server)          echo "mariadb-server" ;;
    mariadb)                 echo "mariadb-client" ;;
    mysql-server)            echo "default-mysql-server" ;;
    mysql)                   echo "default-mysql-client" ;;
    php)                     echo "php8.1-cli" ;;
    php-fpm)                 echo "php8.1-fpm" ;;
    php-pgsql)               echo "php8.1-pgsql" ;;
    php-mysqlnd)             echo "php8.1-mysql" ;;
    php-xml)                 echo "php8.1-xml" ;;
    php-mbstring)            echo "php8.1-mbstring" ;;
    php-json)                echo "php8.1-json" ;;
    php-gd)                  echo "php8.1-gd" ;;
    php-curl)                echo "php8.1-curl" ;;
    php-zip)                 echo "php8.1-zip" ;;
    net-tools)               echo "net-tools" ;;
    bind-utils)              echo "dnsutils" ;;
    nmap)                    echo "nmap" ;;
    traceroute)              echo "traceroute" ;;
    telnet)                  echo "telnet" ;;
    wget)                    echo "wget" ;;
    curl)                    echo "curl" ;;
    vim)                     echo "vim" ;;
    nano)                    echo "nano" ;;
    git)                     echo "git" ;;
    tree)                    echo "tree" ;;
    htop)                    echo "htop" ;;
    sysstat)                 echo "sysstat" ;;
    lsof)                    echo "lsof" ;;
    rsync)                   echo "rsync" ;;
    tar)                     echo "tar" ;;
    unzip)                   echo "unzip" ;;
    zip)                     echo "zip" ;;
    openssl)                 echo "openssl" ;;
    openssh-server)          echo "openssh-server" ;;
    openssh-clients)         echo "openssh-client" ;;
    firewalld)               echo "ufw" ;;
    fail2ban)                echo "fail2ban" ;;
    iptables)                echo "iptables" ;;
    tcpdump)                 echo "tcpdump" ;;
    iftop)                   echo "iftop" ;;
    dnf-automatic)           echo "unattended-upgrades" ;;
    python3)                 echo "python3" ;;
    python3-pip)             echo "python3-pip" ;;
    nodejs)                  echo "nodejs" ;;
    npm)                     echo "npm" ;;
    java-17-openjdk)         echo "openjdk-17-jdk" ;;
    java-11-openjdk)         echo "openjdk-11-jdk" ;;
    crontabs)                echo "cron" ;;
    cronie)                  echo "cron" ;;
    logrotate)               echo "logrotate" ;;
    *)                       echo "$pkg" ;;  # sin traducción, usar tal cual
  esac
}

ACTION="$1"
shift

case "$ACTION" in
  install|in)
    # Filtrar flags como -y y traducir nombres de paquetes
    PKGS=()
    OPTS=()
    for arg in "$@"; do
      if [[ "$arg" == -* ]]; then
        OPTS+=("$arg")
      else
        PKGS+=("$(translate_pkg "$arg")")
      fi
    done
    echo "[dnf→apt] apt-get update && apt-get install ${OPTS[*]} ${PKGS[*]}"
    apt-get update -qq && apt-get install "${OPTS[@]}" "${PKGS[@]}"
    ;;

  update|upgrade|up)
    echo "[dnf→apt] apt-get update && apt-get upgrade -y"
    apt-get update && apt-get upgrade -y
    ;;

  remove|erase|rm)
    PKGS=()
    OPTS=()
    for arg in "$@"; do
      if [[ "$arg" == -* ]]; then
        OPTS+=("$arg")
      else
        PKGS+=("$(translate_pkg "$arg")")
      fi
    done
    echo "[dnf→apt] apt-get remove ${OPTS[*]} ${PKGS[*]}"
    apt-get remove "${OPTS[@]}" "${PKGS[@]}"
    ;;

  search)
    echo "[dnf→apt] apt-cache search $*"
    apt-cache search "$@"
    ;;

  info)
    PKGS=()
    for arg in "$@"; do
      PKGS+=("$(translate_pkg "$arg")")
    done
    echo "[dnf→apt] apt-cache show ${PKGS[*]}"
    apt-cache show "${PKGS[@]}"
    ;;

  list)
    if [[ "$*" == *"--installed"* ]]; then
      echo "[dnf→apt] dpkg -l"
      dpkg -l
    else
      echo "[dnf→apt] apt-cache pkgnames | sort"
      apt-cache pkgnames | sort | head -50
      echo "... (usa: dnf list --installed  para ver instalados)"
    fi
    ;;

  autoremove)
    echo "[dnf→apt] apt-get autoremove -y"
    apt-get autoremove -y
    ;;

  clean)
    echo "[dnf→apt] apt-get clean"
    apt-get clean
    ;;

  repolist)
    echo "[dnf→apt] Repositorios configurados:"
    cat /etc/apt/sources.list 2>/dev/null
    ls /etc/apt/sources.list.d/ 2>/dev/null
    ;;

  history)
    echo "[dnf→apt] Historial de apt (últimas 20 líneas):"
    tail -20 /var/log/apt/history.log 2>/dev/null || echo "(log vacío)"
    ;;

  provides|whatprovides)
    echo "[dnf→apt] apt-file search $*"
    apt-file search "$@" 2>/dev/null || echo "(instala apt-file primero: apt-get install apt-file)"
    ;;

  *)
    echo "[dnf→apt] Comando no reconocido: $ACTION"
    echo ""
    echo "Comandos disponibles (traducidos a apt):"
    echo "  dnf install -y <paquete>    → apt-get install -y <paquete>"
    echo "  dnf update -y               → apt-get update && upgrade"
    echo "  dnf remove <paquete>        → apt-get remove <paquete>"
    echo "  dnf search <término>        → apt-cache search <término>"
    echo "  dnf info <paquete>          → apt-cache show <paquete>"
    echo "  dnf list --installed        → dpkg -l"
    echo ""
    echo "Paquetes Fedora traducidos automáticamente:"
    echo "  postgresql-server → postgresql"
    echo "  httpd             → apache2"
    echo "  php-fpm           → php8.1-fpm"
    echo "  firewalld         → ufw"
    echo "  bind-utils        → dnsutils"
    echo "  openssh-clients   → openssh-client"
    ;;
esac
