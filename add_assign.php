<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');

$courseid = 2;
$assignmodid = 1;
$sec6id = 7;

// Verificar si ya existe
$existing = $DB->get_record('assign', ['course' => $courseid]);
if ($existing) {
    echo "Tarea ya existe: id={$existing->id}\n";
    exit;
}

try {
    $assign = new stdClass();
    $assign->course          = $courseid;
    $assign->name            = 'Proyecto Final - Implementacion LMS';
    $assign->intro           = '<p>Entrega del documento tecnico del proyecto LMS implementado.</p>';
    $assign->introformat     = FORMAT_HTML;
    $assign->alwaysshowdescription = 1;
    $assign->submissiondrafts = 0;
    $assign->requiresubmissionstatement = 0;
    $assign->sendnotifications = 0;
    $assign->sendlatenotifications = 0;
    $assign->duedate         = mktime(23,59,0,6,15,2026);
    $assign->cutoffdate      = 0;
    $assign->allowsubmissionsfromdate = 0;
    $assign->grade           = 100;
    $assign->timemodified    = time();
    $assign->completionsubmit = 0;

    $assignid = assign_add_instance($assign, null);
    echo "Tarea creada: id=$assignid\n";

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
    echo "CM creado: id=$cmid\n";

    rebuild_course_cache($courseid, true);
    echo "DONE\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
