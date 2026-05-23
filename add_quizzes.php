<?php
/**
 * Agrega un quiz de 5 preguntas por cada unidad del curso
 * Moodle 5.x: questions linked via question_bank_entries + question_references
 * Ejecutar: php add_quizzes.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/lib/accesslib.php');

$courseid = $DB->get_field('course', 'id', ['shortname' => 'INFRA-TEC']);
if (!$courseid) {
    $courseid = $DB->get_field('course', 'id', ['shortname' => 'EVA-IT']);
}
if (!$courseid) {
    die("No se encontro el curso.\n");
}

$quizmoduleid = $DB->get_field('modules', 'id', ['name' => 'quiz']);

// ── Limpiar quizzes anteriores del curso ─────────────────────────────────────
echo "==> Limpiando quizzes anteriores del curso...\n";
$oldquizzes = $DB->get_records('quiz', ['course' => $courseid]);
foreach ($oldquizzes as $oq) {
    $slots = $DB->get_records('quiz_slots', ['quizid' => $oq->id]);
    foreach ($slots as $sl) {
        $DB->delete_records('question_references', [
            'component'    => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid'       => $sl->id,
        ]);
    }
    $DB->delete_records('quiz_slots', ['quizid' => $oq->id]);

    $oldcms = $DB->get_records('course_modules', ['course' => $courseid, 'instance' => $oq->id, 'module' => $quizmoduleid]);
    foreach ($oldcms as $ocm) {
        $sec = $DB->get_record('course_sections', ['id' => $ocm->section]);
        if ($sec && $sec->sequence) {
            $seq = array_values(array_filter(explode(',', $sec->sequence), function ($x) use ($ocm) {
                return trim($x) !== (string)$ocm->id;
            }));
            $sec->sequence = implode(',', $seq);
            $DB->update_record('course_sections', $sec);
        }
        $DB->delete_records('context', ['contextlevel' => 70, 'instanceid' => $ocm->id]);
        $DB->delete_records('course_modules', ['id' => $ocm->id]);
    }

    $DB->delete_records('quiz', ['id' => $oq->id]);
    echo "  Eliminado quiz id={$oq->id}: {$oq->name}\n";
}
echo "==> Listo.\n\n";

$now = time();

// ── Banco de preguntas por unidad ────────────────────────────────────────────
$units = [
    1 => [
        'title'   => 'Quiz - Unidad 1: Servidores Linux',
        'section' => 2,
        'questions' => [
            ['q' => '¿Qué comando muestra el uso de disco de todos los sistemas de archivos montados?',
             'correct' => 'df -h',
             'wrong'   => ['du -sh /', 'free -h', 'lsblk -f']],
            ['q' => '¿Cuál es el comando para listar los puertos en escucha con sus procesos?',
             'correct' => 'ss -tulnp',
             'wrong'   => ['netstat', 'ps aux', 'ip addr show']],
            ['q' => '¿Qué comando agrega el usuario "alumno01" al grupo "sudo"?',
             'correct' => 'sudo usermod -aG sudo alumno01',
             'wrong'   => ['sudo useradd -G sudo alumno01', 'sudo addgroup alumno01 sudo', 'sudo chown sudo alumno01']],
            ['q' => '¿Qué permiso octal corresponde a rwxr-xr-x?',
             'correct' => '755',
             'wrong'   => ['777', '644', '700']],
            ['q' => '¿Qué hace el comando "kill -9 PID"?',
             'correct' => 'Termina el proceso de forma forzada sin posibilidad de ignorar la señal',
             'wrong'   => ['Pausa el proceso', 'Reinicia el proceso', 'Manda señal de recarga de configuración']],
        ],
    ],
    2 => [
        'title'   => 'Quiz - Unidad 2: Servicios Web',
        'section' => 3,
        'questions' => [
            ['q' => '¿Cuál es el archivo de configuración del VirtualHost por defecto en Apache en Ubuntu?',
             'correct' => '/etc/apache2/sites-available/000-default.conf',
             'wrong'   => ['/etc/apache2/apache2.conf', '/etc/apache2/conf.d/default.conf', '/etc/httpd/conf/httpd.conf']],
            ['q' => '¿Qué módulo de Apache se debe habilitar para usar .htaccess con reescritura de URLs?',
             'correct' => 'mod_rewrite',
             'wrong'   => ['mod_ssl', 'mod_headers', 'mod_proxy']],
            ['q' => '¿En qué puerto escucha Nginx por defecto para HTTP?',
             'correct' => '80',
             'wrong'   => ['8080', '443', '8443']],
            ['q' => '¿Qué comando genera un certificado SSL auto-firmado con OpenSSL válido por 365 días?',
             'correct' => 'openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout k.key -out c.crt',
             'wrong'   => ['openssl genrsa -out k.key 2048', 'openssl ca -new -days 365', 'ssl-cert-snakeoil --days 365']],
            ['q' => '¿Cuál es el DocumentRoot por defecto de Apache en Ubuntu?',
             'correct' => '/var/www/html',
             'wrong'   => ['/srv/www', '/usr/share/apache2', '/etc/apache2/html']],
        ],
    ],
    3 => [
        'title'   => 'Quiz - Unidad 3: Bases de Datos',
        'section' => 4,
        'questions' => [
            ['q' => '¿Qué comando de psql muestra todas las bases de datos disponibles?',
             'correct' => '\l',
             'wrong'   => ['\dt', '\d', 'SHOW DATABASES;']],
            ['q' => '¿Cuál es el puerto por defecto de PostgreSQL?',
             'correct' => '5432',
             'wrong'   => ['3306', '1433', '27017']],
            ['q' => '¿Qué herramienta se usa para hacer un backup de PostgreSQL en formato comprimido?',
             'correct' => 'pg_dump -F c -f backup.dump moodle_db',
             'wrong'   => ['pg_restore moodle_db', 'psql --backup moodle_db', 'pg_basebackup moodle_db']],
            ['q' => '¿Qué comando de psql describe la estructura de una tabla?',
             'correct' => '\d nombre_tabla',
             'wrong'   => ['DESC nombre_tabla;', 'SHOW COLUMNS FROM nombre_tabla;', '\t nombre_tabla']],
            ['q' => '¿Qué significa ACID en el contexto de bases de datos?',
             'correct' => 'Atomicity, Consistency, Isolation, Durability',
             'wrong'   => ['Access, Control, Index, Data', 'Automated, Concurrent, Integrated, Distributed', 'Advanced, Cached, Indexed, Dynamic']],
        ],
    ],
    4 => [
        'title'   => 'Quiz - Unidad 4: Docker',
        'section' => 5,
        'questions' => [
            ['q' => '¿Cuál es la diferencia entre "docker stop" y "docker kill"?',
             'correct' => 'stop envía SIGTERM (cierre gracioso); kill envía SIGKILL (forzado)',
             'wrong'   => ['stop elimina el contenedor; kill solo lo pausa', 'Son equivalentes', 'stop solo funciona con --force']],
            ['q' => '¿Qué hace "docker compose up -d"?',
             'correct' => 'Levanta los servicios definidos en docker-compose.yml en segundo plano',
             'wrong'   => ['Descarga todas las imágenes sin iniciar', 'Actualiza los contenedores y los reinicia', 'Elimina y recrea todos los volúmenes']],
            ['q' => '¿Cuál es la diferencia entre un volumen Docker y un bind mount?',
             'correct' => 'Los volúmenes son gestionados por Docker; los bind mounts mapean directorios del host directamente',
             'wrong'   => ['No hay diferencia práctica', 'Los bind mounts son más rápidos en producción', 'Los volúmenes solo funcionan en Linux']],
            ['q' => '¿Qué instrucción Dockerfile define el comando por defecto al iniciar el contenedor?',
             'correct' => 'CMD',
             'wrong'   => ['RUN', 'ENTRYPOINT', 'EXEC']],
            ['q' => '¿Para qué sirve "docker inspect <contenedor>"?',
             'correct' => 'Muestra la configuración completa del contenedor en formato JSON (red, volúmenes, env vars, etc.)',
             'wrong'   => ['Ejecuta un shell dentro del contenedor', 'Muestra solo los logs del contenedor', 'Lista las imágenes disponibles']],
        ],
    ],
    5 => [
        'title'   => 'Quiz - Unidad 5: Seguridad y Respaldo',
        'section' => 6,
        'questions' => [
            ['q' => '¿Cuál es el archivo de configuración principal de SSH en Linux?',
             'correct' => '/etc/ssh/sshd_config',
             'wrong'   => ['/etc/ssh/ssh_config', '/etc/sshd.conf', '/usr/etc/sshd_config']],
            ['q' => '¿Qué hace "sudo ufw allow 443/tcp"?',
             'correct' => 'Permite tráfico TCP entrante en el puerto 443 (HTTPS)',
             'wrong'   => ['Bloquea el puerto 443', 'Redirige el puerto 443 al 80', 'Solo aplica a IPv6']],
            ['q' => '¿Cuál es la función principal de fail2ban?',
             'correct' => 'Bloquear IPs que realizan múltiples intentos fallidos de autenticación',
             'wrong'   => ['Cifrar archivos del sistema', 'Monitorear el uso de CPU', 'Gestionar certificados SSL']],
            ['q' => '¿Qué comando crea un archivo tar.gz de /etc/nginx/ con fecha en el nombre?',
             'correct' => 'tar -czf backup_$(date +%Y%m%d).tar.gz /etc/nginx/',
             'wrong'   => ['zip -r backup.zip /etc/nginx/', 'cp -r /etc/nginx/ backup/', 'gzip /etc/nginx/']],
            ['q' => '¿Qué directiva de sshd_config deshabilita el login con contraseña?',
             'correct' => 'PasswordAuthentication no',
             'wrong'   => ['PermitRootLogin no', 'AllowPasswordLogin false', 'DisablePassword yes']],
        ],
    ],
];

$created = 0;
$coursectx = $DB->get_record('context', ['contextlevel' => 50, 'instanceid' => $courseid]);

foreach ($units as $unitnum => $unit) {
    echo "── Unidad $unitnum ──────────────────────────────────────\n";

    // ── 1. Quiz ──────────────────────────────────────────────────────────────
    $quiz = new stdClass();
    $quiz->course             = $courseid;
    $quiz->name               = $unit['title'];
    $quiz->intro              = '<p>Evalúa tus conocimientos de la Unidad ' . $unitnum . '.</p>';
    $quiz->introformat        = 1;
    $quiz->timeopen           = 0;
    $quiz->timeclose          = 0;
    $quiz->timelimit          = 600;
    $quiz->attempts           = 3;
    $quiz->grademethod        = 1;
    $quiz->decimalpoints      = 2;
    $quiz->shuffleanswers     = 1;
    $quiz->preferredbehaviour = 'deferredfeedback';
    $quiz->timecreated        = $now;
    $quiz->timemodified       = $now;
    $quiz->grade              = 10;
    $quiz->sumgrades          = 5;
    $quiz->questionsperpage   = 0;
    $quiz->navmethod          = 'free';
    $quizid = $DB->insert_record('quiz', $quiz);
    echo "  quiz id=$quizid\n";

    // ── 2. Course module ─────────────────────────────────────────────────────
    $cm = new stdClass();
    $cm->course      = $courseid;
    $cm->module      = $quizmoduleid;
    $cm->instance    = $quizid;
    $cm->section     = $unit['section'];
    $cm->added       = $now;
    $cm->visible     = 1;
    $cm->completion       = 2;
    $cm->completionview   = 0;
    $cm->completionpassgrade = 0;
    $cmid = $DB->insert_record('course_modules', $cm);

    // Agregar CM a la seccion (unit['section'] stores the section record ID)
    $section = $DB->get_record('course_sections', ['id' => $unit['section']]);
    if ($section) {
        $seq = $section->sequence ? trim($section->sequence, ',') : '';
        $section->sequence = $seq ? $seq . ',' . $cmid : (string)$cmid;
        $DB->update_record('course_sections', $section);
    }

    // Contexto del modulo (lo crea si no existe)
    $modctx = context_module::instance($cmid);
    $modctxid = $modctx->id;
    echo "  cm id=$cmid, ctx id=$modctxid\n";

    // ── 3. Categoría de preguntas ────────────────────────────────────────────
    $cat = new stdClass();
    $cat->name       = 'Unidad ' . $unitnum;
    $cat->contextid  = $coursectx->id;
    $cat->info       = '';
    $cat->infoformat = 0;
    $cat->stamp      = make_unique_id_code();
    $cat->parent     = 0;
    $cat->sortorder  = 999;
    $catid = $DB->insert_record('question_categories', $cat);

    $slot = 1;

    foreach ($unit['questions'] as $qdata) {
        // ── 4. Pregunta ──────────────────────────────────────────────────────
        $q = new stdClass();
        $q->category              = $catid;
        $q->parent                = 0;
        $q->name                  = substr($qdata['q'], 0, 60);
        $q->questiontext          = '<p>' . $qdata['q'] . '</p>';
        $q->questiontextformat    = 1;
        $q->generalfeedback       = '';
        $q->generalfeedbackformat = 1;
        $q->defaultmark           = 1;
        $q->penalty               = 0.3333333;
        $q->qtype                 = 'multichoice';
        $q->length                = 1;
        $q->stamp                 = make_unique_id_code();
        $q->hidden                = 0;
        $q->timecreated           = $now;
        $q->timemodified          = $now;
        $q->createdby             = 2;
        $q->modifiedby            = 2;
        $qid = $DB->insert_record('question', $q);

        // question_bank_entries (Moodle 4.0+)
        $entry = new stdClass();
        $entry->questioncategoryid = $catid;
        $entry->ownerid            = 2;
        $entry->idnumber           = null;
        $entryid = $DB->insert_record('question_bank_entries', $entry);

        // question_versions
        $ver = new stdClass();
        $ver->questionbankentryid = $entryid;
        $ver->version             = 1;
        $ver->questionid          = $qid;
        $ver->status              = 'ready';
        $DB->insert_record('question_versions', $ver);

        // Opciones multichoice
        $mc = new stdClass();
        $mc->questionid                      = $qid;
        $mc->layout                          = 0;
        $mc->answers                         = '';
        $mc->single                          = 1;
        $mc->shuffleanswers                  = 1;
        $mc->correctfeedback                 = '<p>¡Correcto!</p>';
        $mc->correctfeedbackformat           = 1;
        $mc->partiallycorrectfeedback        = '';
        $mc->partiallycorrectfeedbackformat  = 1;
        $mc->incorrectfeedback               = '<p>Incorrecto. Revisa el material de la unidad.</p>';
        $mc->incorrectfeedbackformat         = 1;
        $mc->answernumbering                 = 'abc';
        $mc->showstandardinstruction         = 0;
        $DB->insert_record('qtype_multichoice_options', $mc);

        // Respuesta correcta
        $ans = new stdClass();
        $ans->question      = $qid;
        $ans->answer        = $qdata['correct'];
        $ans->answerformat  = 0;
        $ans->fraction      = 1.0;
        $ans->feedback      = '';
        $ans->feedbackformat = 0;
        $DB->insert_record('question_answers', $ans);

        // Respuestas incorrectas
        foreach ($qdata['wrong'] as $wrong) {
            $ans2 = new stdClass();
            $ans2->question      = $qid;
            $ans2->answer        = $wrong;
            $ans2->answerformat  = 0;
            $ans2->fraction      = 0;
            $ans2->feedback      = '';
            $ans2->feedbackformat = 0;
            $DB->insert_record('question_answers', $ans2);
        }

        // ── 5. Slot en el quiz (Moodle 5.x: sin questionid) ─────────────────
        $qs = new stdClass();
        $qs->slot            = $slot;
        $qs->quizid          = $quizid;
        $qs->page            = 1;
        $qs->displaynumber   = (string)$slot;
        $qs->requireprevious = 0;
        $qs->maxmark         = 1.0;
        $qs->quizgradeitemid = null;
        $slotid = $DB->insert_record('quiz_slots', $qs);

        // question_references: vincula el slot con la question bank entry
        $ref = new stdClass();
        $ref->usingcontextid    = $modctxid;
        $ref->component         = 'mod_quiz';
        $ref->questionarea      = 'slot';
        $ref->itemid            = $slotid;
        $ref->questionbankentryid = $entryid;
        $ref->version           = null; // null = latest
        $DB->insert_record('question_references', $ref);

        $slot++;
    }

    $DB->set_field('quiz', 'sumgrades', $slot - 1, ['id' => $quizid]);

    echo "✓ Quiz Unidad $unitnum creado (id=$quizid, " . ($slot - 1) . " preguntas)\n\n";
    $created++;
}

require_once($CFG->libdir . '/gradelib.php');
grade_regrade_final_grades($courseid);

echo "Total: $created quizzes creados en el curso.\n";
