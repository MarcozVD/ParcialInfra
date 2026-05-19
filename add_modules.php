<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');

$courseid = 2;

// IDs de modulos (verificados)
$forummodid  = 8;
$assignmodid = 1;

// IDs de secciones (verificados)
$sec0id = 1;
$sec6id = 7;

// ---- Agregar Foro en seccion 0 ----
$forumadd = $DB->get_record('forum', ['course' => $courseid]);
if (!$forumadd) {
    require_once($CFG->dirroot . '/mod/forum/lib.php');
    $forum = new stdClass();
    $forum->course        = $courseid;
    $forum->type          = 'general';
    $forum->name          = 'Foro General - Noticias y Comunicacion';
    $forum->intro         = '<p>Espacio oficial de comunicacion del curso. Aqui se publicaran anuncios importantes, novedades y se fomentara el debate academico.</p>';
    $forum->introformat   = FORMAT_HTML;
    $forum->assessed      = 0;
    $forum->scale         = 0;
    $forum->maxbytes      = 512000;
    $forum->forcesubscribe = 1;
    $forum->timemodified  = time();
    $forumid = forum_add_instance($forum, null);

    $cm = new stdClass();
    $cm->course    = $courseid;
    $cm->module    = $forummodid;
    $cm->instance  = $forumid;
    $cm->section   = $sec0id;
    $cm->visible   = 1;
    $cm->added     = time();
    $cm->completion = 0;
    $cmid = $DB->insert_record('course_modules', $cm);

    $sec0 = $DB->get_record('course_sections', ['id' => $sec0id]);
    $sec0->sequence = trim(($sec0->sequence ? $sec0->sequence . ',' : '') . $cmid, ',');
    $DB->update_record('course_sections', $sec0);
    echo "Foro creado: id=$forumid, cm=$cmid\n";
} else {
    echo "Foro ya existe\n";
}

// ---- Agregar Tarea en seccion 6 ----
$assignexist = $DB->get_record('assign', ['course' => $courseid]);
if (!$assignexist) {
    require_once($CFG->dirroot . '/mod/assign/lib.php');
    $assign = new stdClass();
    $assign->course          = $courseid;
    $assign->name            = 'Proyecto Final - Implementacion LMS';
    $assign->intro           = '<h4>Actividad Evaluativa Final</h4>
<p>Entregar documento tecnico en PDF con:</p>
<ol>
  <li>Arquitectura de la solucion implementada</li>
  <li>Justificacion de decisiones tecnicas (SO, servidor web, BD, contenedores)</li>
  <li>Proceso de instalacion y configuracion con evidencias</li>
  <li>Medidas de seguridad aplicadas</li>
  <li>Plan de respaldo y mantenimiento</li>
  <li>Capturas del EVA funcionando</li>
</ol>
<p><strong>Nota maxima:</strong> 100 puntos. <strong>Fecha limite:</strong> 15 de junio de 2026.</p>';
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
    $cm->course    = $courseid;
    $cm->module    = $assignmodid;
    $cm->instance  = $assignid;
    $cm->section   = $sec6id;
    $cm->visible   = 1;
    $cm->added     = time();
    $cm->completion = 0;
    $cmid = $DB->insert_record('course_modules', $cm);

    $sec6 = $DB->get_record('course_sections', ['id' => $sec6id]);
    $sec6->sequence = trim(($sec6->sequence ? $sec6->sequence . ',' : '') . $cmid, ',');
    $DB->update_record('course_sections', $sec6);
    echo "Tarea creada: id=$assignid, cm=$cmid\n";
} else {
    echo "Tarea ya existe\n";
}

rebuild_course_cache($courseid, true);
echo "Cache actualizado\nDONE\n";
