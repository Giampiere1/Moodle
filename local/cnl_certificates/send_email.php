<?php
/**
 * Certificate send-by-email endpoint — sends a PDF certificate with HTML email.
 *
 * @package    local_cnl_certificates
 * @copyright  2026 Colegio de Notarios de Lima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/cnl_certificates/lib.php');

require_once($CFG->dirroot . '/lib/phpmailer/src/Exception.php');
require_once($CFG->dirroot . '/lib/phpmailer/src/PHPMailer.php');
require_once($CFG->dirroot . '/lib/phpmailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$courseid = required_param('courseid', PARAM_INT);

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

// Confirm POST (CSRF protection)
require_sesskey();

// Get or create certificate
$hours = local_cnl_certificates_get_hours($courseid, $course);
$cert = local_cnl_certificates_get_or_create($courseid, $user->id, $hours);

// Generate PDF
$tempFile = local_cnl_certificates_generate_pdf($user, $course, $cert);

if (!$tempFile || !file_exists($tempFile)) {
    \core\notification::error('Error al generar el certificado en PDF. Contacte al administrador.');
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

// Prepare attachment
$safeName = preg_replace('/[^a-z0-9_\-]/i', '_', $course->shortname);
$attachName = 'Certificado_' . $safeName . '.pdf';

// Copy temp file to Moodle temp dir with a proper name for attachment
$attachTemp = $CFG->tempdir . '/' . $attachName;
copy($tempFile, $attachTemp);
@unlink($tempFile);

// ---------- Build HTML email body ----------
$nombre = fullname($user);
$curso = $course->fullname;
$codigo = $cert->code;
$meses = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre'
];
$fecha = date('d') . ' de ' . $meses[(int) date('n') - 1] . ' de ' . date('Y');

$subject = get_string(
    'emailsubject',
    'local_cnl_certificates',
    (object) ['course' => $curso]
);

$htmlBody = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . htmlspecialchars($subject) . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f0;font-family:Arial,Helvetica,sans-serif;">

  <!-- Wrapper -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f4f0;padding:30px 0;">
    <tr>
      <td align="center">

        <!-- Card -->
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="background-color:#ffffff;border-radius:12px;overflow:hidden;
                      box-shadow:0 4px 24px rgba(0,0,0,0.10);max-width:600px;">

          <!-- Header band -->
          <tr>
            <td style="background:linear-gradient(135deg,#0f3223 0%,#1a5c38 60%,#c8a951 100%);
                       padding:36px 40px 28px 40px;text-align:center;">
              <p style="margin:0 0 6px 0;color:#c8a951;font-size:11px;letter-spacing:3px;
                        text-transform:uppercase;font-weight:600;">Colegio de Notarios de Lima</p>
              <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:700;
                         line-height:1.2;">Plataforma Digital Notarial</h1>
              <!-- Gold divider -->
              <div style="margin:18px auto 0;width:60px;height:3px;
                          background-color:#c8a951;border-radius:2px;"></div>
            </td>
          </tr>

          <!-- Congratulations badge -->
          <tr>
            <td style="background-color:#f7fbf8;padding:28px 40px 20px;text-align:center;
                       border-bottom:2px solid #e8f5ee;">
              <div style="display:inline-block;background:linear-gradient(135deg,#1a5c38,#2e7d55);
                          color:#ffffff;width:60px;height:60px;border-radius:50%;
                          font-size:30px;line-height:60px;text-align:center;
                          box-shadow:0 4px 16px rgba(26,92,56,0.35);margin-bottom:14px;">&#10003;</div>
              <h2 style="margin:0 0 6px;color:#1a5c38;font-size:20px;font-weight:700;">
                ¡Felicitaciones, ' . htmlspecialchars($nombre) . '!</h2>
              <p style="margin:0;color:#4a6741;font-size:13px;">
                Has completado el curso con el 100% de actividades.
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:32px 40px 24px;">
              <p style="margin:0 0 16px;color:#333333;font-size:15px;line-height:1.7;">
                Nos complace informarte que has completado exitosamente el curso:
              </p>

              <!-- Course highlight box -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="background-color:#f0fff4;border-left:4px solid #1a5c38;
                             border-radius:0 8px 8px 0;padding:16px 20px;margin-bottom:20px;">
                    <p style="margin:0 0 4px;color:#2f855a;font-size:11px;
                               letter-spacing:2px;text-transform:uppercase;font-weight:600;">Curso completado</p>
                    <p style="margin:0;color:#1a3a2a;font-size:17px;font-weight:700;
                               line-height:1.3;">' . htmlspecialchars($curso) . '</p>
                  </td>
                </tr>
              </table>

              <p style="margin:20px 0 0;color:#555555;font-size:14px;line-height:1.7;">
                Adjunto a este correo encontrarás tu <strong>certificado oficial en formato PDF</strong>,
                listo para imprimir o compartir.
              </p>
            </td>
          </tr>

          <!-- Certificate details -->
          <tr>
            <td style="padding:0 40px 28px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background-color:#f8f9fa;border-radius:8px;overflow:hidden;">
                <tr style="background-color:#1a5c38;">
                  <td colspan="2" style="padding:10px 18px;">
                    <p style="margin:0;color:#c8a951;font-size:11px;letter-spacing:2px;
                               text-transform:uppercase;font-weight:700;">Datos del Certificado</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:10px 18px;color:#666666;font-size:12px;
                             border-bottom:1px solid #e9ecef;width:40%;">Participante</td>
                  <td style="padding:10px 18px;color:#222222;font-size:12px;font-weight:600;
                             border-bottom:1px solid #e9ecef;">' . htmlspecialchars($nombre) . '</td>
                </tr>
                <tr>
                  <td style="padding:10px 18px;color:#666666;font-size:12px;
                             border-bottom:1px solid #e9ecef;">Código</td>
                  <td style="padding:10px 18px;color:#222222;font-size:12px;font-weight:600;
                             border-bottom:1px solid #e9ecef;font-family:monospace;">' . htmlspecialchars($codigo) . '</td>
                </tr>
                <tr>
                  <td style="padding:10px 18px;color:#666666;font-size:12px;">Fecha de emisión</td>
                  <td style="padding:10px 18px;color:#222222;font-size:12px;font-weight:600;">' . htmlspecialchars($fecha) . '</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color:#f7fbf8;border-top:2px solid #e8f5ee;
                       padding:24px 40px;text-align:center;">
              <p style="margin:0 0 8px;color:#333333;font-size:13px;">
                Atentamente,<br>
                <strong style="color:#1a5c38;">Colegio de Notarios de Lima</strong><br>
                <span style="color:#888888;font-size:12px;">Plataforma Digital Notarial</span>
              </p>
              <div style="margin:16px auto 0;width:40px;height:2px;
                          background-color:#c8a951;border-radius:1px;"></div>
              <p style="margin:14px 0 0;color:#aaaaaa;font-size:11px;line-height:1.6;
                        font-style:italic;">
                Este es un mensaje automático, por favor <strong>no responda</strong> a este correo.<br>
                Si tiene alguna consulta, comuníquese con nosotros a través de los canales oficiales.
              </p>
            </td>
          </tr>

        </table>
        <!-- /Card -->

      </td>
    </tr>
  </table>
  <!-- /Wrapper -->

</body>
</html>';

// Send mail using PHPMailer with custom SMTP configuration
$mail = new PHPMailer(true);
$result = false;
try {
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = "192.168.0.7";
    $mail->Port = 587;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Username = "alertas@infonotaria.pe";
    $mail->Password = "Aocp2022$$";
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ]
    ];

    $mail->setFrom("alertas@infonotaria.pe", "Plataforma Digital Notarial");
    $mail->addAddress($user->email, fullname($user));

    $mail->CharSet = 'UTF-8';
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $htmlBody;
    $mail->AltBody = "Estimado/a {$nombre},\n\nFelicitaciones por completar el curso \"{$curso}\".\nAdjunto encontrará su certificado oficial en PDF.\n\nCódigo: {$codigo}\nFecha: {$fecha}\n\nAtentamente,\nColegio de Notarios de Lima\n\n---\nEste es un mensaje automático, por favor no responda a este correo.";

    $mail->addAttachment($attachTemp, $attachName);

    $mail->send();
    $result = true;
} catch (Exception $e) {
    \core\notification::error("Error de PHPMailer: " . $e->getMessage());
    $result = false;
}

@unlink($attachTemp);

if ($result) {
    \core\notification::success(get_string('emailsent', 'local_cnl_certificates'));
} else {
    \core\notification::error(get_string('emailfailed', 'local_cnl_certificates'));
}

redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
