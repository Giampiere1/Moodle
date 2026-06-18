<?php
define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../config.php');
require_login();

header('Content-Type: application/json');

$instanceid = required_param('instanceid', PARAM_INT);

// Obtener instancia de enrol
try {
    $instance = $DB->get_record('enrol', ['enrol' => 'fee', 'id' => $instanceid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Instancia de matrícula no encontrada.']);
    exit;
}

$cost = (float)$instance->cost;
$currency = $instance->currency;

if ($cost <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'El curso no tiene un costo configurado.']);
    exit;
}

// Generar orderNumber único (máximo 15 caracteres)
// Formato: M + 5 últimos dígitos del timestamp + 5 dígitos del ID de usuario
$orderNumber = 'M' . substr(time(), -5) . str_pad($USER->id, 5, '0', STR_PAD_LEFT);
// transactionId único (máximo 40 caracteres, alfanumérico)
$transactionId = md5(uniqid(rand(), true));

// Configuración de Izipay (Sandbox)
$merchantCode = '4004353';
$publicKey = 'VErethUtraQuxas57wuMuquprADrAHAb';
$keyRSA = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnbZQIF0Fys/1ib3M1XWU\nWRwuTQ5s/xIXG+a7BLGR3WIt5j1/G2ppMWC3c0mSqXTCf2wyihtNm3hirr+edhpb\nKELcMOAZ/RdiJ9S6re9QYoxpOEDIffBpd8I1C0tzSE/XW1eoCa4YceH1fsm9R843\nwvzxhNS1x71PLxKyt7nD+RjAY4gprwO3siylZ+4Rnx5KXO/UleO2St4u0H4xsbig\nqwjoXOEJhCS+C0fZFIMDihno2cXPUhQi5lc3S6ZMSutPqWdBy0GF/FJ30h++0qsg\nA5VfxHnGtPKQVBOdgTT7HUR04KoSb5VNpGGtjNt4eqmewGfZ4gGFPrkkqx9mwsnp\ncQIDAQAB\n-----END PUBLIC KEY-----";
$baseUrl = 'https://sandbox-api-pw.izipay.pe';
$sessionTokenUrl = '/security/v1/Token/Generate';

$amountStr = number_format($cost, 2, '.', '');

$bodyObj = [
    'requestSource' => 'ECOMMERCE',
    'merchantCode' => $merchantCode,
    'orderNumber' => $orderNumber,
    'publicKey' => $publicKey,
    'amount' => $amountStr
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . $sessionTokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyObj));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $publicKey,
    'transactionId: ' . $transactionId,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error al conectar con Izipay: ' . $curlError]);
    exit;
}

$resData = json_decode($response, true);
if (!isset($resData['code']) || $resData['code'] !== '00' || !isset($resData['response']['token'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Izipay devolvió un código de error: ' . (isset($resData['description']) ? $resData['description'] : 'Desconocido'),
        'details' => $resData
    ]);
    exit;
}

$token = $resData['response']['token'];

// Función helper para sanitizar textos según reglas de Izipay
function sanitize_text($v, $default = 'Consumidor') {
    $cleaned = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '', trim($v));
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);
    $cleaned = trim($cleaned);
    return mb_strlen($cleaned) >= 2 ? $cleaned : $default;
}

$firstName = sanitize_text($USER->firstname, 'Student');
$lastName = sanitize_text($USER->lastname, 'CNL');
$email = filter_var($USER->email, FILTER_VALIDATE_EMAIL) ? $USER->email : 'cliente@example.com';
$phone = isset($USER->phone1) && !empty($USER->phone1) ? preg_replace('/\D/', '', $USER->phone1) : '999999999';

echo json_encode([
    'status' => 'success',
    'authorization' => $token,
    'keyRSA' => $keyRSA,
    'merchantCode' => $merchantCode,
    'transactionId' => $transactionId,
    'orderNumber' => $orderNumber,
    'amount' => $amountStr,
    'currency' => $currency,
    'dateTimeTransaction' => date('YmdHis'),
    'buyerId' => str_pad($USER->id, 6, '0', STR_PAD_LEFT),
    'buyer' => [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone
    ]
]);
