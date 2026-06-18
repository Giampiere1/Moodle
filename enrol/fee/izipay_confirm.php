<?php
define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../config.php');
require_login();

header('Content-Type: application/json');

$instanceid = required_param('instanceid', PARAM_INT);
$payloadHttp = required_param('payloadHttp', PARAM_RAW);

try {
    $instance = $DB->get_record('enrol', ['enrol' => 'fee', 'id' => $instanceid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Instancia de matrícula no encontrada.']);
    exit;
}

$data = json_decode($payloadHttp, true);
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Payload JSON inválido.']);
    exit;
}

$code = isset($data['code']) ? $data['code'] : '';
if ($code !== '00') {
    echo json_encode(['success' => false, 'error' => 'El pago no fue autorizado por la pasarela de pagos.']);
    exit;
}

// Verificar si el usuario ya está enrolado para evitar duplicaciones
if ($DB->record_exists('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
    echo json_encode(['success' => true, 'message' => 'El usuario ya se encuentra matriculado.']);
    exit;
}

// Loguear el pago en mdl_payments
$payment = new stdClass();
$payment->component = 'enrol_fee';
$payment->paymentarea = 'fee';
$payment->itemid = $instance->id;
$payment->userid = $USER->id;
$payment->amount = $instance->cost;
$payment->currency = $instance->currency;
$payment->accountid = !empty($instance->customint1) ? $instance->customint1 : 0;
$payment->gateway = 'izipay';
$payment->timecreated = time();
$payment->timemodified = time();

try {
    $paymentid = $DB->insert_record('payments', $payment);
} catch (Exception $e) {
    // Si falla el insert de auditoría, igual procedemos con la matrícula para no perjudicar al usuario,
    // pero guardamos un warning o error de log en Moodle.
    $paymentid = 0;
}

// Matricular al usuario
$plugin = enrol_get_plugin('fee');
if ($instance->enrolperiod) {
    $timestart = time();
    $timeend   = $timestart + $instance->enrolperiod;
} else {
    $timestart = 0;
    $timeend   = 0;
}

try {
    $plugin->enrol_user($instance, $USER->id, $instance->roleid, $timestart, $timeend);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al matricular al usuario en el curso: ' . $e->getMessage()]);
}
