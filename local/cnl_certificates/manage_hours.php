<?php
/**
 * Admin page to configure custom course hours
 *
 * @package    local_cnl_certificates
 * @copyright  2026 Colegio de Notarios de Lima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/cnl_certificates/lib.php');

require_login();
require_capability('local/cnl_certificates:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/cnl_certificates/manage_hours.php'));
$PAGE->set_title(get_string('managehourstitle', 'local_cnl_certificates'));
$PAGE->set_heading(get_string('managehourstitle', 'local_cnl_certificates'));
$PAGE->set_pagelayout('admin');

// Handle POST save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $courseids = optional_param_array('courseid', [], PARAM_INT);
    $hours     = optional_param_array('hours', [], PARAM_INT);

    foreach ($courseids as $i => $cid) {
        if (empty($cid)) continue;
        $h = isset($hours[$i]) ? max(1, (int)$hours[$i]) : CNL_CERTIFICATES_DEFAULT_HOURS;

        $existing = $DB->get_record('local_cnl_cert_hours', ['courseid' => $cid]);
        if ($existing) {
            $existing->hours = $h;
            $DB->update_record('local_cnl_cert_hours', $existing);
        } else {
            $DB->insert_record('local_cnl_cert_hours', (object)['courseid' => $cid, 'hours' => $h]);
        }
    }

    \core\notification::success(get_string('hoursupdated', 'local_cnl_certificates'));
    redirect(new moodle_url('/local/cnl_certificates/manage_hours.php'));
}

// Load all courses (excluding site course)
$courses = $DB->get_records_select('course', 'id > 1', [], 'fullname ASC', 'id, fullname, shortname, summary');

// Load existing custom hours
$customHours = [];
foreach ($DB->get_records('local_cnl_cert_hours') as $r) {
    $customHours[$r->courseid] = $r->hours;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managehourstitle', 'local_cnl_certificates'));

echo html_writer::start_tag('p', ['class' => 'text-muted']);
echo 'Configure las horas de duración para cada curso. Si no se configura, se usará el valor por defecto (' 
    . CNL_CERTIFICATES_DEFAULT_HOURS . ' horas) o el detectado automáticamente en el resumen del curso.';
echo html_writer::end_tag('p');
?>

<form method="post" class="cnl-hours-form">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

    <div class="table-responsive">
        <table class="table table-hover table-striped generaltable">
            <thead class="table-dark">
                <tr>
                    <th>Curso</th>
                    <th>Código</th>
                    <th style="width:140px">Horas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): ?>
                    <?php
                    $autoHours = CNL_CERTIFICATES_DEFAULT_HOURS;
                    if (!empty($c->summary)) {
                        $plain = strip_tags($c->summary);
                        if (preg_match('/(\d+)\s*horas?/i', $plain, $m)) {
                            $autoHours = (int)$m[1];
                        }
                    }
                    $currentHours = isset($customHours[$c->id]) ? $customHours[$c->id] : $autoHours;
                    ?>
                    <tr>
                        <td>
                            <input type="hidden" name="courseid[]" value="<?php echo $c->id; ?>">
                            <strong><?php echo s($c->fullname); ?></strong>
                        </td>
                        <td><code><?php echo s($c->shortname); ?></code></td>
                        <td>
                            <input type="number"
                                   name="hours[]"
                                   value="<?php echo $currentHours; ?>"
                                   min="1" max="999"
                                   class="form-control form-control-sm">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-success">
            <i class="fa fa-save"></i> Guardar cambios
        </button>
        <a href="<?php echo $CFG->wwwroot; ?>/admin/index.php" class="btn btn-secondary ml-2">Cancelar</a>
    </div>
</form>

<?php echo $OUTPUT->footer(); ?>
