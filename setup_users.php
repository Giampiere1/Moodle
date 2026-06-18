<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/moodlelib.php');

global $DB, $CFG;

$password = 'Password123!*';
$courseid = 2;

function get_or_create_user(array $data, string $password): stdClass {
    global $DB, $CFG;
    $existing = $DB->get_record('user', ['username' => $data['username'], 'deleted' => 0]);
    if ($existing) {
        echo "  → '{$data['username']}' existe (ID={$existing->id}). Actualizando...\n";
        $existing->firstname    = $data['firstname'];
        $existing->lastname     = $data['lastname'];
        $existing->email        = $data['email'];
        $existing->password     = hash_internal_user_password($password);
        $existing->timemodified = time();
        $DB->update_record('user', $existing);
        return $existing;
    }
    echo "  → Creando '{$data['username']}'...\n";
    $user               = new stdClass();
    $user->auth         = 'manual';
    $user->confirmed    = 1;
    $user->mnethostid   = $CFG->mnet_localhost_id;
    $user->username     = $data['username'];
    $user->password     = hash_internal_user_password($password);
    $user->firstname    = $data['firstname'];
    $user->lastname     = $data['lastname'];
    $user->email        = $data['email'];
    $user->lang         = 'es';
    $user->timecreated  = time();
    $user->timemodified = time();
    $user->id = $DB->insert_record('user', $user);
    return $user;
}

function enrol_user_directly(int $courseid, int $userid, int $roleid): void {
    global $DB;
    $enrolInstance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
    if (!$enrolInstance) {
        $inst               = new stdClass();
        $inst->enrol        = 'manual';
        $inst->status       = 0;
        $inst->courseid     = $courseid;
        $inst->sortorder    = 0;
        $inst->timecreated  = time();
        $inst->timemodified = time();
        $inst->id = $DB->insert_record('enrol', $inst);
        $enrolInstance = $DB->get_record('enrol', ['id' => $inst->id]);
    }
    $existing = $DB->get_record('user_enrolments', ['enrolid' => $enrolInstance->id, 'userid' => $userid]);
    if (!$existing) {
        $ue               = new stdClass();
        $ue->enrolid      = $enrolInstance->id;
        $ue->userid       = $userid;
        $ue->status       = 0;
        $ue->timestart    = 0;
        $ue->timeend      = 0;
        $ue->modifierid   = 2;
        $ue->timecreated  = time();
        $ue->timemodified = time();
        $DB->insert_record('user_enrolments', $ue);
    } else {
        $DB->set_field('user_enrolments', 'status', 0, ['id' => $existing->id]);
    }
    $courseCtx = $DB->get_record('context', ['contextlevel' => 50, 'instanceid' => $courseid]);
    if (!$courseCtx) { echo "  ERROR: sin contexto del curso\n"; return; }
    $existingRA = $DB->get_record('role_assignments', ['roleid' => $roleid, 'contextid' => $courseCtx->id, 'userid' => $userid, 'component' => '']);
    if (!$existingRA) {
        $ra               = new stdClass();
        $ra->roleid       = $roleid;
        $ra->contextid    = $courseCtx->id;
        $ra->userid       = $userid;
        $ra->timemodified = time();
        $ra->modifierid   = 2;
        $ra->component    = '';
        $ra->itemid       = 0;
        $ra->sortorder    = 0;
        $DB->insert_record('role_assignments', $ra);
    }
}

$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    echo "ERROR: No existe curso ID={$courseid}\n";
    exit(1);
}
echo "✓ Curso: ID={$course->id} | {$course->shortname}\n\n";

$roleTeacher = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
$roleStudent = $DB->get_record('role', ['shortname' => 'student'],        '*', MUST_EXIST);

$users = [
    ['username'=>'usr_admin',   'firstname'=>'Admin',   'lastname'=>'Test', 'email'=>'usr_admin@cnl.test',   'role'=>'admin'],
    ['username'=>'usr_teacher', 'firstname'=>'Teacher', 'lastname'=>'Test', 'email'=>'usr_teacher@cnl.test', 'role'=>'editingteacher'],
    ['username'=>'usr_student', 'firstname'=>'Student', 'lastname'=>'Test', 'email'=>'usr_student@cnl.test', 'role'=>'student'],
];

foreach ($users as $data) {
    echo "─── {$data['username']} ───\n";
    $user = get_or_create_user($data, $password);
    if ($data['role'] === 'admin') {
        $admins = array_filter(explode(',', $CFG->siteadmins), fn($v) => trim($v) !== '');
        if (!in_array((string)$user->id, $admins)) {
            $admins[] = (string)$user->id;
            $newAdmins = implode(',', $admins);
            set_config('siteadmins', $newAdmins);
            $CFG->siteadmins = $newAdmins;
            echo "  ✓ Añadido a siteadmins\n";
        } else {
            echo "  ✓ Ya es administrador\n";
        }
    } elseif ($data['role'] === 'editingteacher') {
        enrol_user_directly($courseid, $user->id, $roleTeacher->id);
        echo "  ✓ Profesor en CEPLA\n";
    } elseif ($data['role'] === 'student') {
        enrol_user_directly($courseid, $user->id, $roleStudent->id);
        echo "  ✓ Estudiante en CEPLA\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════\nRESULTADO\n═══════════════════════════════════\n";
$adminIds  = array_filter(explode(',', $CFG->siteadmins), fn($v) => trim($v) !== '');
$courseCtx = $DB->get_record('context', ['contextlevel' => 50, 'instanceid' => $courseid]);
foreach (['usr_admin','usr_teacher','usr_student'] as $uname) {
    $u = $DB->get_record('user', ['username' => $uname, 'deleted' => 0]);
    if ($u) {
        echo "✓ {$uname} (ID={$u->id}) | {$u->firstname} {$u->lastname}\n";
        if (in_array((string)$u->id, $adminIds)) echo "  └─ Admin del sitio ✓\n";
        if ($courseCtx) {
            $roles = $DB->get_records_sql("SELECT r.shortname FROM {role_assignments} ra JOIN {role} r ON r.id=ra.roleid WHERE ra.userid=:uid AND ra.contextid=:ctxid", ['uid'=>$u->id,'ctxid'=>$courseCtx->id]);
            foreach ($roles as $r) echo "  └─ Rol CEPLA: {$r->shortname} ✓\n";
        }
    } else {
        echo "✗ {$uname} NO ENCONTRADO\n";
    }
}
echo "\n✅ Listo.\n";
