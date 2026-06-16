<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Core library functions for local_cnl_certificates
 *
 * @package    local_cnl_certificates
 * @copyright  2026 Colegio de Notarios de Lima
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('CNL_CERTIFICATES_TEMPLATE', 'C:\\proyectos\\Plantilla.docx');
define('CNL_CERTIFICATES_DEFAULT_HOURS', 40);

/**
 * Get course completion percentage for a user.
 *
 * @param int $courseid
 * @param int $userid
 * @return float percentage (0-100)
 */
function local_cnl_certificates_get_completion_pct($courseid, $userid) {
    global $DB;

    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

    // If course completion is not enabled, return 0
    if (!$course->enablecompletion) {
        return 0;
    }

    // Count total completion criteria
    $criteria = $DB->count_records('course_completion_criteria', ['course' => $courseid]);

    if ($criteria === 0) {
        // No criteria defined — check activity completions instead
        $sql = "SELECT COUNT(*) as total
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                WHERE cm.course = :courseid AND cm.completion > 0";
        $total = $DB->count_records_sql($sql, ['courseid' => $courseid]);
        if ($total === 0) {
            return 0;
        }

        $sql2 = "SELECT COUNT(*) as done
                 FROM {course_modules_completion} cmc
                 JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                 WHERE cm.course = :courseid AND cmc.userid = :userid
                   AND cmc.completionstate >= 1";
        $done = $DB->count_records_sql($sql2, ['courseid' => $courseid, 'userid' => $userid]);
        return ($total > 0) ? round(($done / $total) * 100, 0) : 0;
    }

    // Use course_completions table
    $completion = $DB->get_record('course_completions', [
        'course' => $courseid,
        'userid' => $userid
    ]);

    if ($completion && $completion->timecompleted) {
        return 100;
    }

    // Count met criteria
    $met = $DB->count_records('course_completion_crit_compl', [
        'course' => $courseid,
        'userid' => $userid
    ]);

    return ($criteria > 0) ? round(($met / $criteria) * 100, 0) : 0;
}

/**
 * Get hours assigned to a course (from DB override, then summary parse, then default).
 *
 * @param int $courseid
 * @param object|null $course Optional prefetched course record
 * @return int hours
 */
function local_cnl_certificates_get_hours($courseid, $course = null) {
    global $DB;

    // 1. Check custom hours table
    $custom = $DB->get_record('local_cnl_cert_hours', ['courseid' => $courseid]);
    if ($custom) {
        return (int)$custom->hours;
    }

    // 2. Try to parse from course summary
    if ($course === null) {
        $course = $DB->get_record('course', ['id' => $courseid]);
    }
    if ($course && !empty($course->summary)) {
        $plain = strip_tags($course->summary);
        if (preg_match('/(\d+)\s*horas?/i', $plain, $m)) {
            return (int)$m[1];
        }
    }

    // 3. Default
    return CNL_CERTIFICATES_DEFAULT_HOURS;
}

/**
 * Clean split XML placeholders in a DOCX document.xml string.
 * DOCX files often split text across multiple <w:r> runs. This function
 * merges adjacent runs and removes spell-check tags so placeholders like
 * ${fecha} are searchable as a single string.
 *
 * @param string $xml Raw XML content
 * @return string Cleaned XML
 */
function local_cnl_certificates_clean_xml($xml) {
    // Remove spelling error markers that Word inserts between characters
    $xml = preg_replace('/<w:proofErr[^>]*\/>/i', '', $xml);

    // Merge adjacent text runs: </w:t></w:r>...<w:r...><w:t...>
    // We do this repeatedly until stable to handle chains of splits
    $prev = '';
    while ($prev !== $xml) {
        $prev = $xml;
        $xml  = preg_replace(
            '/<\/w:t><\/w:r>(\s*)<w:r[^>]*>(\s*)<w:t[^>]*>/i',
            '',
            $xml
        );
    }

    return $xml;
}

/**
 * Generate a QR code PNG image (binary string) for a given URL.
 *
 * @param string $url URL to encode
 * @param int $cellsize Size in pixels of each QR cell (default 4)
 * @return string|false PNG binary data or false on failure
 */
function local_cnl_certificates_generate_qr($url, $cellsize = 4) {
    global $CFG;
    require_once($CFG->libdir . '/tcpdf/tcpdf_barcodes_2d.php');

    $barcode = new TCPDF2DBarcode($url, 'QRCODE,H');
    $data = $barcode->getBarcodePngData($cellsize, $cellsize, [0, 0, 0]);
    return $data;
}

/**
 * Generate a unique certificate code.
 *
 * Format: CNL-YYYY-NNNNN
 *
 * @param int $courseid
 * @param int $userid
 * @return string
 */
function local_cnl_certificates_generate_code($courseid, $userid) {
    global $DB;

    $year  = date('Y');
    $count = $DB->count_records('local_cnl_certificates') + 1;
    $code  = sprintf('CNL-%s-%05d', $year, $count);

    // Ensure uniqueness
    while ($DB->record_exists('local_cnl_certificates', ['code' => $code])) {
        $count++;
        $code = sprintf('CNL-%s-%05d', $year, $count);
    }

    return $code;
}

/**
 * Get or create a certificate record for a user in a course.
 *
 * @param int $courseid
 * @param int $userid
 * @param int $hours
 * @return object Certificate record
 */
function local_cnl_certificates_get_or_create($courseid, $userid, $hours) {
    global $DB, $CFG;

    $existing = $DB->get_record('local_cnl_certificates', [
        'userid'   => $userid,
        'courseid' => $courseid,
    ]);

    if ($existing) {
        return $existing;
    }

    $code = local_cnl_certificates_generate_code($courseid, $userid);

    $record = new stdClass();
    $record->code        = $code;
    $record->userid      = $userid;
    $record->courseid    = $courseid;
    $record->hours       = $hours;
    $record->timecreated = time();

    $record->id = $DB->insert_record('local_cnl_certificates', $record);

    return $record;
}

/**
 * Build the public verification URL for a given certificate code.
 *
 * @param string $code
 * @return string
 */
function local_cnl_certificates_verification_url($code) {
    global $CFG;
    return $CFG->wwwroot . '/local/cnl_certificates/verify.php?code=' . urlencode($code);
}

/**
 * Generate a DOCX certificate file as a temp file path.
 *
 * Replaces placeholders in the template:
 *   ${nombre}, ${curso}, ${tipo}, ${horas}, ${fecha}, ${qr}, ${codigo}, ${enlace}
 *
 * @param object $user  Moodle user object
 * @param object $course Moodle course object
 * @param object $cert  Certificate DB record
 * @return string|false Path to generated temp file, or false on failure
 */
function local_cnl_certificates_generate_docx($user, $course, $cert) {
    global $CFG;

    $template = CNL_CERTIFICATES_TEMPLATE;

    if (!file_exists($template)) {
        debugging("Certificate template not found: $template", DEBUG_DEVELOPER);
        return false;
    }

    // Verification URL and QR
    $verifyurl = local_cnl_certificates_verification_url($cert->code);
    $qrpng     = local_cnl_certificates_generate_qr($verifyurl, 4);

    // Build replacement values
    $nombre = fullname($user);
    $curso  = $course->fullname;
    $tipo   = 'Participante';
    $horas  = $cert->hours . ' horas';
    // Format date in Spanish: Monday 16 de junio de 2026
    $meses  = ['enero','febrero','marzo','abril','mayo','junio',
               'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $fecha  = date('d') . ' de ' . $meses[(int)date('n') - 1] . ' de ' . date('Y');
    $codigo = $cert->code;
    $enlace = $verifyurl;

    // Open the DOCX (ZIP)
    $zip = new ZipArchive();
    if ($zip->open($template) !== true) {
        debugging("Could not open template DOCX: $template", DEBUG_DEVELOPER);
        return false;
    }

    // Files to process text replacements in
    $xmlFiles = ['word/document.xml', 'word/footer1.xml'];

    $replacements = [
        '${nombre}' => $nombre,
        '${curso}'  => $curso,
        '${tipo}'   => $tipo,
        '${horas}'  => $horas,
        '${fecha}'  => $fecha,
        '${codigo}' => $codigo,
        '${enlace}' => $enlace,
    ];

    $modifiedXmls = [];
    foreach ($xmlFiles as $xmlFile) {
        $content = $zip->getFromName($xmlFile);
        if ($content === false) {
            continue;
        }
        // Clean up split placeholders
        $content = local_cnl_certificates_clean_xml($content);
        // Replace text placeholders
        foreach ($replacements as $placeholder => $value) {
            $content = str_replace(
                htmlspecialchars($placeholder, ENT_XML1, 'UTF-8'),
                htmlspecialchars($value, ENT_XML1, 'UTF-8'),
                $content
            );
            // Also replace unescaped version (safe since values are entity-encoded above)
            $content = str_replace($placeholder, htmlspecialchars($value, ENT_XML1, 'UTF-8'), $content);
        }
        $modifiedXmls[$xmlFile] = $content;
    }

    // Handle ${qr} placeholder: replace the whole text box paragraph with an image
    // We need to add the QR image to the ZIP and update document.xml.rels
    $qrRelId  = 'rIdCNLQR';
    $qrImgPath = 'word/media/cnl_qr.png';

    // Modify document.xml to replace ${qr} with an image reference
    if (isset($modifiedXmls['word/document.xml']) && $qrpng) {
        $docXml = $modifiedXmls['word/document.xml'];

        // Build OOXML inline image markup
        $imgMarkup = local_cnl_certificates_build_image_xml($qrRelId, 100, 100);

        // Replace the cleaned ${qr} run with the image markup
        // The ${qr} is inside a text run: <w:r><w:t>${qr}</w:t></w:r>
        $docXml = preg_replace(
            '/<w:r[^>]*>\s*<w:rPr[^>]*>.*?<\/w:rPr>\s*<w:t[^>]*>\$\{qr\}<\/w:t>\s*<\/w:r>/is',
            $imgMarkup,
            $docXml
        );
        // Also try simpler form without rPr
        $docXml = str_replace(
            '<w:r><w:t>${qr}</w:t></w:r>',
            $imgMarkup,
            $docXml
        );

        $modifiedXmls['word/document.xml'] = $docXml;
    }

    // Create a writable copy of the ZIP in a temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'cnlcert_') . '.docx';
    copy($template, $tempFile);

    $tempZip = new ZipArchive();
    if ($tempZip->open($tempFile) !== true) {
        return false;
    }

    // Write modified XML files back
    foreach ($modifiedXmls as $zipEntry => $content) {
        $tempZip->addFromString($zipEntry, $content);
    }

    // Add QR PNG image to the archive
    if ($qrpng) {
        $tempZip->addFromString($qrImgPath, $qrpng);

        // Update document.xml.rels to include the QR image relationship
        $relsContent = $tempZip->getFromName('word/_rels/document.xml.rels');
        if ($relsContent !== false) {
            $newRel = '<Relationship Id="' . $qrRelId . '" '
                    . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
                    . 'Target="../word/media/cnl_qr.png"/>';
            $relsContent = str_replace('</Relationships>', $newRel . '</Relationships>', $relsContent);
            $tempZip->addFromString('word/_rels/document.xml.rels', $relsContent);
        }
    }

    $tempZip->close();

    return $tempFile;
}

/**
 * Build the OOXML <w:r> markup for an inline image.
 *
 * @param string $relId  Relationship ID (rId)
 * @param int    $widthPt  Width in points (1 pt = 12700 EMU)
 * @param int    $heightPt Height in points
 * @return string
 */
function local_cnl_certificates_build_image_xml($relId, $widthPt = 100, $heightPt = 100) {
    $cx = $widthPt  * 12700;
    $cy = $heightPt * 12700;

    return '<w:r><w:rPr/><w:drawing>
  <wp:inline distT="0" distB="0" distL="0" distR="0"
      xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">
    <wp:extent cx="' . $cx . '" cy="' . $cy . '"/>
    <wp:docPr id="1001" name="cnl_qr"/>
    <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
      <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
        <pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
          <pic:nvPicPr>
            <pic:cNvPr id="1001" name="cnl_qr"/>
            <pic:cNvPicPr/>
          </pic:nvPicPr>
          <pic:blipFill>
            <a:blip r:embed="' . $relId . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>
            <a:stretch><a:fillRect/></a:stretch>
          </pic:blipFill>
          <pic:spPr>
            <a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>
            <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
          </pic:spPr>
        </pic:pic>
      </a:graphicData>
    </a:graphic>
  </wp:inline>
</w:drawing></w:r>';
}
