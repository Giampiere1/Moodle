<?php
/**
 * Block CNL Certificate — displays download/email buttons when course is 100% complete.
 *
 * @package    block_cnl_certificate
 * @copyright  2026 Colegio de Notarios de Lima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/cnl_certificates/lib.php');

class block_cnl_certificate extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_cnl_certificate');
    }

    public function applicable_formats() {
        return array('course-view' => true);
    }

    public function has_config() {
        return false;
    }

    public function get_content() {
        global $USER, $COURSE, $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Only logged‑in users (no guests)
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $courseid = $COURSE->id;
        $userid   = $USER->id;

        // Capability check
        $context = context_course::instance($courseid);
        if (!has_capability('local/cnl_certificates:view', $context)) {
            return $this->content;
        }

        $pct = local_cnl_certificates_get_completion_pct($courseid, $userid);

        // URLs for download and email actions
        $downloadUrl = new moodle_url('/local/cnl_certificates/download.php', array('courseid' => $courseid));
        $emailUrl    = new moodle_url('/local/cnl_certificates/send_email.php', array('courseid' => $courseid, 'sesskey' => sesskey()));

        // Build HTML (inline CSS for premium look)
        $html = '<div class="cnl-cert-block">';
        $html .= '<div class="cnl-cert-header">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">';
        $html .= '<path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/>';
        $html .= '<path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/>';
        $html .= '</svg>';
        $html .= '<span>' . get_string('pluginname', 'block_cnl_certificate') . '</span>';
        $html .= '</div>';

        if ($pct >= 100) {
            // Completed view
            $html .= '<div class="cnl-cert-completed">';
            $html .= '<div class="cnl-cert-badge">✓</div>';
            $html .= '<p class="cnl-cert-congrats">' . get_string('completed', 'block_cnl_certificate') . '</p>';
            $html .= '</div>';
            // Download button
            $html .= '<a href="' . $downloadUrl->out(false) . '" class="cnl-cert-btn cnl-cert-btn--primary">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">';
            $html .= '<path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>'; 
            $html .= '<path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>'; 
            $html .= '</svg> ' . get_string('download', 'local_cnl_certificates');
            $html .= '</a>';
            // Email form
            $html .= '<form method="post" action="' . $emailUrl->out(false) . '" style="margin-top:8px">';
            $html .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
            $html .= '<button type="submit" class="cnl-cert-btn cnl-cert-btn--secondary">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">';
            $html .= '<path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>'; 
            $html .= '</svg> ' . get_string('sendemail', 'local_cnl_certificates');
            $html .= '</button></form>';
        } else {
            // Progress view
            $html .= '<div class="cnl-cert-progress-wrap">';
            $html .= '<p class="cnl-cert-progress-label">' . get_string('progress', 'block_cnl_certificate') . ': <strong>' . (int)$pct . '%</strong></p>';
            $html .= '<div class="cnl-cert-progressbar"><div class="cnl-cert-progressbar-inner" style="width:' . (int)$pct . '%"></div></div>';
            $html .= '<p class="cnl-cert-hint">' . get_string('notcompleted', 'local_cnl_certificates') . '</p>';
            $html .= '</div>';
        }

        $html .= '</div>'; // close block

        // Inline premium CSS
        $html .= '<style>
            .cnl-cert-block{font-family:"Inter",sans-serif;padding:4px 0;}
            .cnl-cert-header{display:flex;align-items:center;gap:8px;color:#1a5c38;font-weight:700;font-size:.9rem;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #e8f5ee;}
            .cnl-cert-completed{text-align:center;margin-bottom:14px;}
            .cnl-cert-badge{width:52px;height:52px;background:linear-gradient(135deg,#1a5c38,#2e7d55);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 10px;box-shadow:0 4px 16px rgba(26,92,56,.35);animation:cnlPop .4s ease;}
            @keyframes cnlPop{from{transform:scale(.6);opacity:0}to{transform:scale(1);opacity:1}}
            .cnl-cert-congrats{font-size:.85rem;color:#2f855a;font-weight:600;margin:0;}
            .cnl-cert-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:10px 0;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;cursor:pointer;border:none;transition:all .2s;}
            .cnl-cert-btn--primary{background:linear-gradient(135deg,#1a5c38,#2e7d55);color:#fff;box-shadow:0 3px 12px rgba(26,92,56,.3);} 
            .cnl-cert-btn--primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(26,92,56,.4);} 
            .cnl-cert-btn--secondary{background:#fff;color:#1a5c38;border:2px solid #1a5c38;width:100%;box-sizing:border-box;}
            .cnl-cert-btn--secondary:hover{background:#f0fff4;}
            .cnl-cert-progress-wrap{text-align:center;}
            .cnl-cert-progress-label{font-size:.85rem;color:#4a5568;margin-bottom:8px;}
            .cnl-cert-progressbar{background:#e2e8f0;border-radius:20px;height:10px;overflow:hidden;margin-bottom:10px;}
            .cnl-cert-progressbar-inner{height:100%;background:linear-gradient(90deg,#1a5c38,#c8a951);border-radius:20px;transition:width .6s ease;}
            .cnl-cert-hint{font-size:.78rem;color:#718096;margin:0;}
        </style>';

        $this->content->text = $html;
        return $this->content;
    }
}
?>
