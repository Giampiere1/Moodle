<?php
/**
 * Certificate send-by-email endpoint
 *
 * @package    local_cnl_certificates
 * @copyright  2026 Colegio de Notarios de Lima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/cnl_certificates/lib.php');

$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);

$context = context_course::instance($courseid);
require_capability('local/cnl_certificates:view', $context);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$user   = $USER;

// Check 100% completion
$pct = local_cnl_certificates_get_completion_pct($courseid, $user->id);
if ($pct < 100) {
    \core\notification::error(get_string('notcompleted', 'local_cnl_certificates'));
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

// Confirm POST (CSRF protection)
require_sesskey();

// Get or create certificate
$hours = local_cnl_certificates_get_hours($courseid, $course);
$cert  = local_cnl_certificates_get_or_create($courseid, $user->id, $hours);

// Generate DOCX
$tempFile = local_cnl_certificates_generate_docx($user, $course, $cert);

if (!$tempFile || !file_exists($tempFile)) {
    \core\notification::error('Error al generar el certificado. Contacte al administrador.');
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

// Build email
$verifyUrl = local_cnl_certificates_verification_url($cert->code);

$subject = get_string('emailsubject', 'local_cnl_certificates',
    (object)['course' => $course->fullname]);

$body = get_string('emailbody', 'local_cnl_certificates', (object)[
    'username' => fullname($user),
    'course'   => $course->fullname,
    'code'     => $cert->code,
    'url'      => $verifyUrl,
]);

// Prepare attachment
$safeName = preg_replace('/[^a-z0-9_\-]/i', '_', $course->shortname);
$attachName = 'Certificado_' . $safeName . '.docx';

// Copy temp file to Moodle temp dir with a proper name for attachment
$attachTemp = $CFG->tempdir . '/' . $attachName;
copy($tempFile, $attachTemp);
@unlink($tempFile);

// Send mail using Moodle API
$supportuser = core_user::get_support_user();
$result = email_to_user(
    $user,
    $supportuser,
    $subject,
    $body,
    '',             // HTML body
    $attachTemp,    // attachment file path
    $attachName     // attachment display name
);

@unlink($attachTemp);

if ($result) {
    \core\notification::success(get_string('emailsent', 'local_cnl_certificates'));
} else {
    \core\notification::error(get_string('emailfailed', 'local_cnl_certificates'));
}

redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
