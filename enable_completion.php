<?php
/**
 * Activa completion tracking en el curso y en todas las actividades
 * Ejecutar: php enable_completion.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

// 1. Habilitar completion tracking a nivel de sitio
set_config('enablecompletion', 1);

// 2. Obtener el curso
$course = $DB->get_record('course', ['shortname' => 'INFRA-TEC']);
if (!$course) {
    $course = $DB->get_record('course', ['shortname' => 'EVA-IT']);
}
if (!$course) {
    die("Curso no encontrado.\n");
}

// 3. Habilitar en el curso
$DB->set_field('course', 'enablecompletion', 1, ['id' => $course->id]);
$DB->set_field('course', 'completionnotify', 1, ['id' => $course->id]);
echo "✓ Completion habilitado en el curso '{$course->fullname}'\n";

// 4. Habilitar en todos los course modules del curso
$cms = $DB->get_records('course_modules', ['course' => $course->id]);
$count = 0;
foreach ($cms as $cm) {
    // completion=1 → "visto" (view), completion=2 → condiciones (grade/etc)
    $mod = $DB->get_field('modules', 'name', ['id' => $cm->module]);
    if (in_array($mod, ['quiz', 'assign'])) {
        // Para evaluaciones: completado al recibir cualquier nota
        $DB->set_field('course_modules', 'completion', 2, ['id' => $cm->id]);
        $DB->set_field('course_modules', 'completionview', 0, ['id' => $cm->id]);
        $DB->set_field('course_modules', 'completionpassgrade', 0, ['id' => $cm->id]);
    } else {
        // Para páginas, foros, URLs: completado al verlo
        $DB->set_field('course_modules', 'completion', 1, ['id' => $cm->id]);
        $DB->set_field('course_modules', 'completionview', 1, ['id' => $cm->id]);
    }
    $count++;
}
echo "✓ Completion activado en $count actividades/recursos\n";

// 5. Limpiar caché del curso
require_once($CFG->dirroot . '/course/lib.php');
rebuild_course_cache($course->id, true);

echo "✓ Caché del curso actualizado\n";
echo "\nAhora los alumnos ven una barra de progreso por unidad.\n";
echo "Páginas/URLs/foros: se marcan al abrirlos.\n";
echo "Quizzes: se marcan al obtener una nota.\n";
