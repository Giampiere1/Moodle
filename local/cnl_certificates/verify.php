<?php
/**
 * Public certificate verification page
 *
 * @package    local_cnl_certificates
 * @copyright  2026 Colegio de Notarios de Lima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/cnl_certificates/lib.php');

$code = optional_param('code', '', PARAM_ALPHANUMEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/cnl_certificates/verify.php'));
$PAGE->set_title(get_string('verificationtitle', 'local_cnl_certificates'));
$PAGE->set_heading('Colegio de Notarios de Lima');
$PAGE->set_pagelayout('base');

// Inject premium styles
$PAGE->requires->css(new moodle_url('/local/cnl_certificates/styles/verify.css'));

$cert   = null;
$user   = null;
$course = null;

if ($code) {
    $cert = $DB->get_record('local_cnl_certificates', ['code' => $code]);
    if ($cert) {
        $user   = $DB->get_record('user',   ['id' => $cert->userid],   '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cert->courseid], '*', MUST_EXIST);
    }
}

echo $OUTPUT->header();
?>

<div class="cnl-verify-wrapper">
    <div class="cnl-verify-card">

        <!-- Header branding -->
        <div class="cnl-verify-header">
            <img src="<?php echo $OUTPUT->image_url('cnl_logo', 'local_cnl_certificates'); ?>"
                 alt="Colegio de Notarios de Lima"
                 onerror="this.style.display='none'"
                 class="cnl-logo">
            <h1><?php echo get_string('verificationtitle', 'local_cnl_certificates'); ?></h1>
            <p><?php echo get_string('verificationdesc', 'local_cnl_certificates'); ?></p>
        </div>

        <!-- Search form -->
        <form method="get" action="" class="cnl-verify-form">
            <div class="cnl-input-group">
                <input type="text"
                       name="code"
                       value="<?php echo s($code); ?>"
                       placeholder="Ej: CNL-2026-00001"
                       class="cnl-input"
                       required>
                <button type="submit" class="cnl-btn-verify">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.156a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/>
                    </svg>
                    <?php echo get_string('verifybutton', 'local_cnl_certificates'); ?>
                </button>
            </div>
        </form>

        <!-- Result -->
        <?php if ($code): ?>
            <?php if ($cert && $user && $course): ?>
                <div class="cnl-result cnl-result--valid">
                    <div class="cnl-result-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                        </svg>
                    </div>
                    <h2><?php echo get_string('verifysuccess', 'local_cnl_certificates'); ?></h2>
                    <p class="cnl-valid-msg"><?php echo get_string('certificatevalidmessage', 'local_cnl_certificates'); ?></p>

                    <div class="cnl-cert-details">
                        <div class="cnl-detail-row">
                            <span class="cnl-detail-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                                <?php echo get_string('student', 'local_cnl_certificates'); ?>
                            </span>
                            <span class="cnl-detail-value"><?php echo fullname($user); ?></span>
                        </div>
                        <div class="cnl-detail-row">
                            <span class="cnl-detail-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h11A1.5 1.5 0 0 1 15 2.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 13.5v-11zM2.5 2a.5.5 0 0 0-.5.5v11a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5v-11a.5.5 0 0 0-.5-.5h-11z"/>
                                </svg>
                                <?php echo get_string('course', 'local_cnl_certificates'); ?>
                            </span>
                            <span class="cnl-detail-value"><?php echo s($course->fullname); ?></span>
                        </div>
                        <div class="cnl-detail-row">
                            <span class="cnl-detail-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                </svg>
                                <?php echo get_string('hours', 'local_cnl_certificates'); ?>
                            </span>
                            <span class="cnl-detail-value"><?php echo (int)$cert->hours; ?> horas</span>
                        </div>
                        <div class="cnl-detail-row">
                            <span class="cnl-detail-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                </svg>
                                <?php echo get_string('date', 'local_cnl_certificates'); ?>
                            </span>
                            <span class="cnl-detail-value"><?php
                                $meses = ['enero','febrero','marzo','abril','mayo','junio',
                                          'julio','agosto','septiembre','octubre','noviembre','diciembre'];
                                $t = (int)$cert->timecreated;
                                echo date('d', $t) . ' de ' . $meses[(int)date('n', $t) - 1] . ' de ' . date('Y', $t);
                            ?></span>
                        </div>
                        <div class="cnl-detail-row">
                            <span class="cnl-detail-label">Código</span>
                            <span class="cnl-detail-value cnl-code"><?php echo s($cert->code); ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="cnl-result cnl-result--invalid">
                    <div class="cnl-result-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </div>
                    <h2><?php echo get_string('verifyerror', 'local_cnl_certificates'); ?></h2>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="cnl-verify-footer">
            <p>Colegio de Notarios de Lima &mdash; <a href="https://www.notarios.org.pe" target="_blank">www.notarios.org.pe</a></p>
        </div>
    </div>
</div>

<?php echo $OUTPUT->footer(); ?>
