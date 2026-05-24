<?php
/**
 * Crea quiz_sections para los quizzes 9-13 (requerido por Moodle 5.x).
 * Sin quiz_sections, el layout del intento queda vacío -> noquestionsfound.
 * Ejecutar: php fix_quiz_sections.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$quiz_ids = [9, 10, 11, 12, 13];

foreach ($quiz_ids as $quizid) {
    $existing = $DB->get_record('quiz_sections', ['quizid' => $quizid, 'firstslot' => 1]);
    if ($existing) {
        echo "Quiz $quizid: quiz_sections ya existe (id={$existing->id}).\n";
        continue;
    }
    $sec = new stdClass();
    $sec->quizid           = $quizid;
    $sec->firstslot        = 1;
    $sec->heading          = '';
    $sec->shufflequestions = 0;
    $id = $DB->insert_record('quiz_sections', $sec);
    echo "✓ Quiz $quizid: quiz_sections creado (id=$id).\n";
}

// Borrar intentos con layout vacío para que los nuevos se creen correctamente
$deleted = 0;
$attempts = $DB->get_records_select('quiz_attempts',
    "quiz IN (9,10,11,12,13) AND (layout IS NULL OR layout = '')");
foreach ($attempts as $attempt) {
    $uniqueid = $attempt->uniqueid;
    $qattempt_ids = $DB->get_fieldset_select('question_attempts', 'id', 'questionusageid = ?', [$uniqueid]);
    if ($qattempt_ids) {
        list($in, $params) = $DB->get_in_or_equal($qattempt_ids);
        $DB->delete_records_select('question_attempt_steps', "questionattemptid $in", $params);
        $DB->delete_records('question_attempts', ['questionusageid' => $uniqueid]);
    }
    $DB->delete_records('question_usages', ['id' => $uniqueid]);
    $DB->delete_records('quiz_attempts', ['id' => $attempt->id]);
    echo "  ✓ Intento roto id={$attempt->id} eliminado.\n";
    $deleted++;
}
if ($deleted) echo "$deleted intento(s) con layout vacío eliminados.\n";

purge_all_caches();
echo "\n✓ Caché purgada.\n";
echo "=== Listo. Ahora inicia el quiz nuevamente. ===\n";
