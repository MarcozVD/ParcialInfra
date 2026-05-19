<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$courseid = 2;
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// ---- Actualizar secciones con descripciones ----
$sections = [
    0 => [
        'name'    => '',
        'summary' => '<h3>Bienvenidos al EVA - Infraestructura Tecnologica</h3>
<p>Este Entorno Virtual de Aprendizaje (EVA) esta disenado para apoyar los procesos academicos de la asignatura <strong>Infraestructura Tecnologica</strong>.</p>
<p><strong>Objetivos del curso:</strong></p>
<ul>
  <li>Instalar y administrar servidores Linux (Fedora, Ubuntu, Debian)</li>
  <li>Configurar servicios web (Apache, Nginx)</li>
  <li>Gestionar bases de datos (MySQL, PostgreSQL)</li>
  <li>Desplegar aplicaciones con Docker y Docker Compose</li>
  <li>Aplicar medidas basicas de seguridad en infraestructuras</li>
  <li>Documentar y justificar decisiones de arquitectura tecnologica</li>
</ul>
<p><strong>Resultados de Aprendizaje:</strong> Al finalizar el curso, el estudiante sera capaz de disenar, implementar, configurar y documentar una solucion de infraestructura tecnologica funcional, segura y escalable.</p>',
    ],
    1 => [
        'name'    => 'Unidad 1 - Servidores Linux',
        'summary' => '<p>Instalacion y administracion de servidores Linux. Comandos basicos, gestion de usuarios, permisos, servicios y logs del sistema.</p>
<p><strong>Temas:</strong> Instalacion de Fedora/Ubuntu Server, administracion basica, SSH, systemd, firewalld.</p>',
    ],
    2 => [
        'name'    => 'Unidad 2 - Servicios Web',
        'summary' => '<p>Configuracion de servidores web Apache y Nginx. Virtual hosts, SSL/TLS, modulos y optimizacion de rendimiento.</p>
<p><strong>Temas:</strong> Apache httpd, Nginx, Virtual Hosts, mod_rewrite, certificados SSL.</p>',
    ],
    3 => [
        'name'    => 'Unidad 3 - Bases de Datos',
        'summary' => '<p>Gestion de motores de bases de datos relacionales. Instalacion, configuracion, seguridad y respaldo de MySQL, MariaDB y PostgreSQL.</p>
<p><strong>Temas:</strong> MySQL, MariaDB, PostgreSQL, usuarios y privilegios, backup y restore.</p>',
    ],
    4 => [
        'name'    => 'Unidad 4 - Contenedores Docker',
        'summary' => '<p>Virtualizacion ligera con contenedores. Docker, Docker Compose, imagenes, volumenes, redes y orquestacion basica.</p>
<p><strong>Temas:</strong> Docker CLI, Dockerfile, docker-compose, volumenes persistentes, redes Docker.</p>',
    ],
    5 => [
        'name'    => 'Unidad 5 - Seguridad y Respaldo',
        'summary' => '<p>Seguridad basica en infraestructuras de TI. Firewall, SSH seguro, actualizaciones, monitoreo y estrategias de respaldo.</p>
<p><strong>Temas:</strong> firewalld, fail2ban, SSH hardening, backups, monitoreo con herramientas basicas.</p>',
    ],
    6 => [
        'name'    => 'Evaluacion Final - Hackathon LMS',
        'summary' => '<p>Proyecto integrador: diseno, implementacion y sustentacion de una plataforma LMS basada en Moodle con arquitectura de infraestructura justificada.</p>',
    ],
];

foreach ($sections as $sectionnum => $data) {
    $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
    if ($section) {
        $section->name    = $data['name'];
        $section->summary = $data['summary'];
        $section->summaryformat = FORMAT_HTML;
        $DB->update_record('course_sections', $section);
        echo "Seccion $sectionnum actualizada\n";
    } else {
        echo "Seccion $sectionnum no encontrada\n";
    }
}

// ---- Agregar Forum en seccion 0 ----
$forumadd = $DB->get_record('forum', ['course' => $courseid, 'type' => 'general']);
if (!$forumadd) {
    require_once($CFG->dirroot . '/mod/forum/lib.php');
    $forum = new stdClass();
    $forum->course       = $courseid;
    $forum->type         = 'general';
    $forum->name         = 'Foro General - Noticias y Comunicacion';
    $forum->intro        = '<p>Espacio oficial de comunicacion del curso. Aqui se publicaran anuncios importantes, novedades y se fomentara el debate academico entre estudiantes y docentes.</p>';
    $forum->introformat  = FORMAT_HTML;
    $forum->assessed     = 0;
    $forum->scale        = 0;
    $forum->maxbytes     = 512000;
    $forum->forcesubscribe = 1;
    $forum->timemodified = time();
    $forumid = forum_add_instance($forum, null);

    // Agregar al course_modules
    $cm = new stdClass();
    $cm->course   = $courseid;
    $cm->module   = $DB->get_field('modules', 'id', ['name' => 'forum']);
    $cm->instance = $forumid;
    $cm->section  = $DB->get_field('course_sections', 'id', ['course' => $courseid, 'section' => 0]);
    $cm->visible  = 1;
    $cm->added    = time();
    $cm->completion = 0;
    $cmid = $DB->insert_record('course_modules', $cm);

    // Agregar al sequence de la seccion 0
    $sec0 = $DB->get_record('course_sections', ['course' => $courseid, 'section' => 0]);
    $sec0->sequence = trim($sec0->sequence . ',' . $cmid, ',');
    $DB->update_record('course_sections', $sec0);
    echo "Foro creado: id=$forumid, cm=$cmid\n";
} else {
    echo "Foro ya existe\n";
}

// ---- Agregar Assignment (Tarea evaluativa) en seccion 6 ----
$assignexist = $DB->get_record('assign', ['course' => $courseid]);
if (!$assignexist) {
    require_once($CFG->dirroot . '/mod/assign/lib.php');
    $assign = new stdClass();
    $assign->course          = $courseid;
    $assign->name            = 'Proyecto Final - Implementacion LMS';
    $assign->intro           = '<h4>Descripcion de la Actividad Evaluativa</h4>
<p>Cada equipo debera entregar un <strong>documento tecnico en PDF</strong> que incluya:</p>
<ol>
  <li>Descripcion de la arquitectura propuesta e implementada</li>
  <li>Justificacion de las decisiones tecnicas (SO, servidor web, BD, despliegue)</li>
  <li>Proceso de instalacion y configuracion documentado con evidencias</li>
  <li>Analisis de seguridad basica aplicada</li>
  <li>Plan de respaldo y mantenimiento</li>
  <li>Capturas de pantalla del funcionamiento del EVA</li>
</ol>
<p><strong>Criterios de evaluacion:</strong> Arquitectura (20%), Implementacion (25%), Documentacion (20%), Seguridad (15%), Sustentacion (20%).</p>';
    $assign->introformat     = FORMAT_HTML;
    $assign->alwaysshowdescription = 1;
    $assign->submissiondrafts = 0;
    $assign->requiresubmissionstatement = 0;
    $assign->sendnotifications = 0;
    $assign->sendlatenotifications = 0;
    $assign->duedate         = mktime(23,59,0,6,15,2026);
    $assign->cutoffdate      = mktime(23,59,0,6,20,2026);
    $assign->allowsubmissionsfromdate = mktime(0,0,0,5,1,2026);
    $assign->grade           = 100;
    $assign->timemodified    = time();
    $assignid = assign_add_instance($assign, null);

    $cm = new stdClass();
    $cm->course   = $courseid;
    $cm->module   = $DB->get_field('modules', 'id', ['name' => 'assign']);
    $cm->instance = $assignid;
    $cm->section  = $DB->get_field('course_sections', 'id', ['course' => $courseid, 'section' => 6]);
    $cm->visible  = 1;
    $cm->added    = time();
    $cm->completion = 0;
    $cmid = $DB->insert_record('course_modules', $cm);

    $sec6 = $DB->get_record('course_sections', ['course' => $courseid, 'section' => 6]);
    $sec6->sequence = trim($sec6->sequence . ',' . $cmid, ',');
    $DB->update_record('course_sections', $sec6);
    echo "Tarea creada: id=$assignid, cm=$cmid\n";
} else {
    echo "Tarea ya existe\n";
}

// Limpiar cache del curso
rebuild_course_cache($courseid, true);
echo "Cache del curso actualizado\n";
echo "DONE\n";
