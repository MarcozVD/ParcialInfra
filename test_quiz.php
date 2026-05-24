<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

$quizid = 9;
$quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('quiz', $quizid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

echo "CM id: {$cm->id}, Context id: {$context->id}\n";

$slots = \mod_quiz\question\bank\qbank_helper::get_question_structure($quizid, $context);
echo "Slots cargados por qbank_helper: " . count($slots) . "\n";
foreach ($slots as $slot) {
    echo "  Slot {$slot->slot}: questionid=" . ($slot->questionid ?? 'NULL') . "\n";
}

$quizobj = \mod_quiz\quiz_settings::create($quizid);
$has = $quizobj->has_questions();
echo "has_questions() = " . ($has ? 'TRUE' : 'FALSE') . "\n";
