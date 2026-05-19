<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');

// 1. Crear categoria
$cat = $DB->get_record('course_categories', ['name' => 'Ingenieria de Sistemas']);
if (!$cat) {
    $catdata = new stdClass();
    $catdata->name = 'Ingenieria de Sistemas';
    $catdata->idnumber = 'IS';
    $catdata->description = 'Facultad de Ingenieria de Sistemas';
    $catdata->visible = 1;
    $catdata->parent = 0;
    $catid = $DB->insert_record('course_categories', $catdata);
    $DB->execute("UPDATE {course_categories} SET path = '/{$catid}', depth = 1 WHERE id = {$catid}");
    echo "Categoria creada: id=$catid\n";
} else {
    $catid = $cat->id;
    echo "Categoria existente: id=$catid\n";
}

// 2. Crear curso
$course = $DB->get_record('course', ['shortname' => 'INFRA-TEC']);
if ($course) {
    echo "Curso ya existe: id={$course->id}\n";
    $courseid = $course->id;
} else {
    $coursedata = new stdClass();
    $coursedata->fullname    = 'Infraestructura Tecnologica';
    $coursedata->shortname   = 'INFRA-TEC';
    $coursedata->summary     = '<p>Curso de Infraestructura Tecnologica para la carrera de Ingenieria de Sistemas. Temas: servidores Linux, redes, bases de datos, contenedores Docker y seguridad.</p>';
    $coursedata->summaryformat = FORMAT_HTML;
    $coursedata->category    = $catid;
    $coursedata->format      = 'topics';
    $coursedata->numsections = 6;
    $coursedata->lang        = 'es';
    $coursedata->startdate   = mktime(0,0,0,1,1,2026);
    $coursedata->enddate     = mktime(0,0,0,12,31,2026);
    $coursedata->visible     = 1;
    $coursedata->showgrades  = 1;
    $coursedata->enablecompletion = 1;
    $newcourse = create_course($coursedata);
    $courseid  = $newcourse->id;
    echo "Curso creado: id=$courseid\n";
}

// 3. Enrolar usuarios
$adminuser = $DB->get_record('user', ['username' => 'admin']);
$docente   = $DB->get_record('user', ['username' => 'docente01']);
$est1      = $DB->get_record('user', ['username' => 'estudiante01']);
$est2      = $DB->get_record('user', ['username' => 'estudiante02']);
$est3      = $DB->get_record('user', ['username' => 'estudiante03']);

$manualenrol = enrol_get_plugin('manual');
$instance    = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
if (!$instance) {
    $instanceid = $manualenrol->add_instance($DB->get_record('course', ['id' => $courseid]));
    $instance = $DB->get_record('enrol', ['id' => $instanceid]);
}

$roleTeacher = $DB->get_record('role', ['shortname' => 'editingteacher']);
$roleStudent = $DB->get_record('role', ['shortname' => 'student']);

foreach ([$adminuser, $docente] as $u) {
    $manualenrol->enrol_user($instance, $u->id, $roleTeacher->id);
    echo "Enrolado como docente: {$u->username}\n";
}
foreach ([$est1, $est2, $est3] as $u) {
    $manualenrol->enrol_user($instance, $u->id, $roleStudent->id);
    echo "Enrolado como estudiante: {$u->username}\n";
}

echo "DONE - Curso id=$courseid\n";
