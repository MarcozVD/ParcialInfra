<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');

$courseid = 2;
$assignmodid = 1;
$sec6id = 7;

$existing = $DB->get_record('assign', ['course' => $courseid]);
if ($existing) {
    echo "Tarea ya existe: id={$existing->id}\n";
    exit;
}

// Limpiar CM provisionales sin instancia
$DB->delete_records('course_modules', ['course' => $courseid, 'module' => $assignmodid, 'instance' => 0]);
echo "CM provisionales limpiados\n";

// Insertar assign directamente
$assigndata = new stdClass();
$assigndata->course          = $courseid;
$assigndata->name            = 'Proyecto Final - Implementacion LMS';
$assigndata->intro           = '<p>Entrega del documento tecnico del proyecto LMS implementado durante la Hackathon.</p>';
$assigndata->introformat     = FORMAT_HTML;
$assigndata->alwaysshowdescription = 1;
$assigndata->nosubmissions   = 0;
$assigndata->submissiondrafts = 0;
$assigndata->sendnotifications = 0;
$assigndata->sendlatenotifications = 0;
$assigndata->sendstudentnotifications = 1;
$assigndata->duedate         = mktime(23,59,0,6,15,2026);
$assigndata->cutoffdate      = 0;
$assigndata->gradingduedate  = 0;
$assigndata->allowsubmissionsfromdate = 0;
$assigndata->grade           = 100;
$assigndata->timemodified    = time();
$assigndata->requiresubmissionstatement = 0;
$assigndata->completionsubmit = 0;
$assigndata->teamsubmission  = 0;
$assigndata->requireallteammemberssubmit = 0;
$assigndata->teamsubmissiongroupingid = 0;
$assigndata->blindmarking    = 0;
$assigndata->hidegrader      = 0;
$assigndata->revealidentities = 0;
$assigndata->attemptreopenmethod = 'none';
$assigndata->maxattempts     = -1;
$assigndata->markingworkflow = 0;
$assigndata->markingallocation = 0;
$assigndata->markinganonymous = 0;
$assigndata->preventsubmissionnotingroup = 0;
$assigndata->activityformat  = 0;
$assigndata->timelimit       = 0;
$assigndata->submissionattachments = 0;
$assigndata->gradepenalty    = 0;

$assignid = $DB->insert_record('assign', $assigndata);
echo "Assign insertado: id=$assignid\n";

// Crear course_module
$cm = new stdClass();
$cm->course    = $courseid;
$cm->module    = $assignmodid;
$cm->instance  = $assignid;
$cm->section   = $sec6id;
$cm->visible   = 1;
$cm->added     = time();
$cm->completion = 0;
$cm->score     = 0;
$cm->indent    = 0;
$cm->groupmode = 0;
$cm->groupingid = 0;
$cmid = $DB->insert_record('course_modules', $cm);
echo "CM creado: id=$cmid\n";

// Actualizar seccion
$sec6 = $DB->get_record('course_sections', ['id' => $sec6id]);
$sec6->sequence = trim(($sec6->sequence ? $sec6->sequence . ',' : '') . $cmid, ',');
$DB->update_record('course_sections', $sec6);

rebuild_course_cache($courseid, true);
echo "DONE\n";
