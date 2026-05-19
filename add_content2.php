<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');

$courseid = 2;

// Verificar modulos disponibles
$forummod  = $DB->get_record('modules', ['name' => 'forum']);
$assignmod = $DB->get_record('modules', ['name' => 'assign']);
echo "Forum module id: " . ($forummod ? $forummod->id : 'NO') . "\n";
echo "Assign module id: " . ($assignmod ? $assignmod->id : 'NO') . "\n";

// Verificar secciones
$secs = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC', 'id,section,name');
foreach ($secs as $s) {
    echo "Seccion {$s->section}: id={$s->id} name='{$s->name}'\n";
}
