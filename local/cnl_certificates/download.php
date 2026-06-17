<?php
/**
 * Certificate download endpoint — delivers a PDF certificate.
 *
 * @package    local_cnl_certificates
 * @copyright  2026 Colegio de Notarios de Lima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/cnl_certificates/lib.php');

// Parameters
$courseid = required_param('courseid', PARAM_INT);

// Require login
require_login($courseid);

$context = context_course::instance($courseid);
require_capability('local/cnl_certificates:view', $context);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);

// Check 100% completion
$pct = local_cnl_certificates_get_completion_pct($courseid, $user->id);
if ($pct < 100) {
    \core\notification::error(get_string('notcompleted', 'local_cnl_certificates'));
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

// Get or create certificate record
$hours = local_cnl_certificates_get_hours($courseid, $course);
$cert = local_cnl_certificates_get_or_create($courseid, $user->id, $hours);

// Generate PDF
$tempFile = local_cnl_certificates_generate_pdf($user, $course, $cert);

if (!$tempFile || !file_exists($tempFile)) {
    \core\notification::error('Error al generar el certificado en PDF. Contacte al administrador.');
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

// Prepare filename (clean unsafe chars)
$safeName = preg_replace('/[^a-z0-9_\-]/i', '_', $course->shortname);
$filename = 'Certificado_' . $safeName . '_' . $user->id . '.pdf';

// Stream PDF to browser
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tempFile));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($tempFile);

// Clean up
@unlink($tempFile);
exit;
