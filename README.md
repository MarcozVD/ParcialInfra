# EVA - Infraestructura Tecnológica

Plataforma LMS Moodle 5.1.4 desplegada con Docker sobre Fedora Server 43.  
Proyecto final del parcial de **Infraestructura Tecnológica**.

## Stack tecnológico

| Componente | Tecnología |
|---|---|
| LMS | Moodle 5.1.4 |
| Web server | Apache 2.4 (mod_rewrite) |
| Base de datos | PostgreSQL 15 |
| Runtime | PHP 8.2 |
| Contenedores | Docker 29.4 + Compose v5.1 |
| Sistema operativo | Fedora Server 43 |

## Arquitectura

```
┌─────────────────────────────────────────────┐
│              Fedora Server 43               │
│                                             │
│  ┌──────────────────┐  ┌─────────────────┐  │
│  │   moodle-app     │  │   moodle-db     │  │
│  │  php:8.2-apache  │  │  postgres:15    │  │
│  │  puerto 80       │  │  puerto 5432    │  │
│  └────────┬─────────┘  └────────┬────────┘  │
│           │   moodle-network    │            │
│           └─────────────────────┘            │
│  Volúmenes: moodledata / postgresql_data     │
└─────────────────────────────────────────────┘
```

## Requisitos

- Docker 20+ y Docker Compose v2+
- Puerto 80 libre en el host (deshabilitar Apache/Nginx del host si aplica)

## Despliegue

```bash
# Clonar el repositorio
git clone https://github.com/MarcozVD/ParcialInfra.git
cd ParcialInfra

# Levantar los contenedores (primera vez: construye la imagen y ejecuta instalador)
docker compose up -d --build

# Ver logs del instalador (tarda ~2-3 min en la primera ejecución)
docker logs -f moodle-app
```

Moodle queda disponible en `http://<IP-DEL-SERVIDOR>` una vez que los logs muestren `==> Iniciando Apache...`.

## Credenciales por defecto

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` | `Admin@Infra2024` |
| Docente | `docente01` | `Docente@2024` |
| Estudiante | `estudiante01` | `Estudiante@2024` |
| Estudiante | `estudiante02` | `Estudiante@2024` |
| Estudiante | `estudiante03` | `Estudiante@2024` |

## Notas técnicas importantes

- **Moodle 5.x usa `public/` como DocumentRoot** — el `Dockerfile` parchea `000-default.conf` para apuntar a `/var/www/html/public`.
- **`config.php` se copia, no se enlaza** — `file_exists()` de PHP retorna `false` para symlinks que cruzan puntos de montaje Docker; el `entrypoint.sh` usa `cp` para persistir la configuración en el volumen.
- **`assign_add_instance()` requiere CM previo en Moodle 5.x** — la tarea se creó con `$DB->insert_record()` directo para evitar el error de contexto de módulo.

## Documentación

Ver [DOCUMENTO_TECNICO.md](DOCUMENTO_TECNICO.md) para la documentación completa del proyecto.
