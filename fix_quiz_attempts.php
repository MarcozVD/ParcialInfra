<?php
/**
 * Elimina los intentos de quiz con layout vacío y reconstruye
 * el grade summary para cada quiz.
 * Ejecutar: php fix_quiz_attempts.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$quiz_ids = [9, 10, 11, 12, 13];

foreach ($quiz_ids as $quizid) {
    // Obtener intentos con layout vacío
    $attempts = $DB->get_records_select(
        'quiz_attempts',
        "quiz = ? AND (layout IS NULL OR layout = '')",
        [$quizid]
    );

    if (empty($attempts)) {
        echo "Quiz $quizid: sin intentos rotos.\n";
        continue;
    }

    echo "Quiz $quizid: eliminando " . count($attempts) . " intento(s) con layout vacío...\n";

    foreach ($attempts as $attempt) {
        $uniqueid = $attempt->uniqueid;

        // Borrar en orden: steps → attempts → usages → quiz_attempt
        $qattempt_ids = $DB->get_fieldset_select('question_attempts', 'id', 'questionusageid = ?', [$uniqueid]);
        if ($qattempt_ids) {
            list($in, $params) = $DB->get_in_or_equal($qattempt_ids);
            $DB->delete_records_select('question_attempt_steps', "questionattemptid $in", $params);
            $DB->delete_records('question_attempts', ['questionusageid' => $uniqueid]);
        }
        $DB->delete_records('question_usages', ['id' => $uniqueid]);
        $DB->delete_records('quiz_attempts', ['id' => $attempt->id]);

        echo "  ✓ Intento id={$attempt->id} (uniqueid=$uniqueid, user={$attempt->userid}) eliminado.\n";
    }
}

// Limpiar caché
purge_all_caches();
echo "\n✓ Caché purgada.\n";
echo "=== Listo. Los alumnos pueden iniciar intentos nuevos. ===\n";
