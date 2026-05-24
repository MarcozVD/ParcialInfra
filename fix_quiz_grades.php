<?php
/**
 * Crea quiz_grade_items para los quizzes 9-13 y enlaza los slots.
 * Ejecutar: php fix_quiz_grades.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');

$quiz_ids = [9, 10, 11, 12, 13];
$fixed = 0;

foreach ($quiz_ids as $quizid) {
    $quiz = $DB->get_record('quiz', ['id' => $quizid]);
    if (!$quiz) {
        echo "Quiz $quizid no encontrado, saltando.\n";
        continue;
    }

    // Crear quiz_grade_items si no existe para este quiz
    $existing = $DB->get_record('quiz_grade_items', ['quizid' => $quizid]);
    if (!$existing) {
        $gi = new stdClass();
        $gi->quizid    = $quizid;
        $gi->sortorder = 1;
        $gi->name      = '';
        $gi->id = $DB->insert_record('quiz_grade_items', $gi);
        echo "✓ quiz_grade_items creado para quiz $quizid (id={$gi->id})\n";
    } else {
        $gi = $existing;
        echo "  quiz_grade_items ya existe para quiz $quizid (id={$gi->id})\n";
    }

    // Actualizar todos los slots de este quiz con quizgradeitemid
    $updated = $DB->execute(
        "UPDATE {quiz_slots} SET quizgradeitemid = ? WHERE quizid = ? AND (quizgradeitemid IS NULL OR quizgradeitemid != ?)",
        [$gi->id, $quizid, $gi->id]
    );
    $slots = $DB->count_records('quiz_slots', ['quizid' => $quizid, 'quizgradeitemid' => $gi->id]);
    echo "✓ $slots slots actualizados con quizgradeitemid={$gi->id}\n";

    // Recalcular grade items del gradebook para este quiz
    quiz_update_grades($quiz);
    echo "✓ Gradebook actualizado para quiz '{$quiz->name}'\n\n";
    $fixed++;
}

// Limpiar caches de Moodle
purge_all_caches();
echo "✓ Caché purgada.\n";
echo "\n=== $fixed quizzes corregidos. ===\n";
echo "Recarga el navegador e intenta el quiz nuevamente.\n";
