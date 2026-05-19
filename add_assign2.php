<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');

$courseid = 2;
$assignmodid = 1;
$sec6id = 7;

$existing = $DB->get_record('assign', ['course' => $courseid]);
if ($existing) {
    echo "Tarea ya existe: id={$existing->id}\n";
    exit;
}

try {
    // PASO 1: Crear course_module primero (con instance=0)
    $cm = new stdClass();
    $cm->course    = $courseid;
    $cm->module    = $assignmodid;
    $cm->instance  = 0;
    $cm->section   = $sec6id;
    $cm->visible   = 1;
    $cm->added     = time();
    $cm->completion = 0;
    $cm->score     = 0;
    $cm->indent    = 0;
    $cm->groupmode = 0;
    $cm->groupingid = 0;
    $cmid = $DB->insert_record('course_modules', $cm);
    echo "CM provisional creado: id=$cmid\n";

    // PASO 2: Crear contexto del modulo
    context_helper::create_instances(CONTEXT_MODULE, $cmid);

    // PASO 3: Crear instancia de assign con coursemodule
    $assign = new stdClass();
    $assign->course              = $courseid;
    $assign->coursemodule        = $cmid;
    $assign->name                = 'Proyecto Final - Implementacion LMS';
    $assign->intro               = '<p>Entrega del documento tecnico del proyecto LMS implementado durante la Hackathon.</p>';
    $assign->introformat         = FORMAT_HTML;
    $assign->alwaysshowdescription = 1;
    $assign->submissiondrafts    = 0;
    $assign->requiresubmissionstatement = 0;
    $assign->sendnotifications   = 0;
    $assign->sendlatenotifications = 0;
    $assign->duedate             = mktime(23,59,0,6,15,2026);
    $assign->cutoffdate          = 0;
    $assign->allowsubmissionsfromdate = 0;
    $assign->grade               = 100;
    $assign->timemodified        = time();
    $assign->completionsubmit    = 0;
    $assign->teamsubmission      = 0;
    $assign->requireallteammemberssubmit = 0;
    $assign->blindmarking        = 0;
    $assign->hidegrader          = 0;
    $assign->revealidentities    = 0;
    $assign->maxattempts         = -1;

    $assignid = assign_add_instance($assign, null);
    echo "Tarea creada: id=$assignid\n";

    // PASO 4: Actualizar course_module con la instancia real
    $DB->set_field('course_modules', 'instance', $assignid, ['id' => $cmid]);

    // PASO 5: Agregar al sequence de la seccion
    $sec6 = $DB->get_record('course_sections', ['id' => $sec6id]);
    $sec6->sequence = trim(($sec6->sequence ? $sec6->sequence . ',' : '') . $cmid, ',');
    $DB->update_record('course_sections', $sec6);

    rebuild_course_cache($courseid, true);
    echo "DONE - Tarea y CM configurados correctamente\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    // Limpiar CM provisional si fallo
    if (isset($cmid)) {
        $DB->delete_records('course_modules', ['id' => $cmid]);
    }
}
