<?php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['operator_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$location   = $data['location'] ?? 'Unknown Location';
$operator   = $data['operator'] ?? 'Unknown Operator';
$confidence = isset($data['confidence']) ? floatval($data['confidence']) : 0;
$video      = $data['video'] ?? 'N/A';

//Fast2SMS Config
$fast2sms_api_key = 'API KEY';
$alert_phone = 'PHONE NUMBER';

if (!preg_match('/^[6-9][0-9]{9}$/', $alert_phone)) {
    echo json_encode(['error' => 'Invalid alert phone number']);
    exit;
}

// SMS message (final professional format)
$message = 
    "ACCIDENT ALERT! Location: {$location}. Operator: {$operator}. Confidence: {$confidence}%. Respond immediately.";

// Hard safety trim (Fast2SMS safe)
$message = substr($message, 0, 159);

if (strlen($message) > 160) {
    $message = "ACCIDENT at {$location}. Confidence: {$confidence}%. By: {$operator}.";
}

$url = "https://www.fast2sms.com/dev/bulkV2";

$postData = [
    'route'   => 'q',
    'message' => $message,
    'numbers' => $alert_phone,
    'flash'   => '0',
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($postData),
    CURLOPT_HTTPHEADER     => [
        'authorization: ' . $fast2sms_api_key,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response   = curl_exec($curl);
$httpCode   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError  = curl_error($curl);
curl_close($curl);

$smsResult = json_decode($response, true);
$sms_success = ($httpCode === 200 && isset($smsResult['return']) && $smsResult['return'] === true);

//Log alert in DB
try {
    $conn  = getDB();
    $op_id = $_SESSION['operator_id'];

    $stmt = $conn->prepare(
        "INSERT INTO alert_logs (operator_id, location, confidence, sms_sent, sent_at)
         VALUES (?, ?, ?, ?, NOW())"
    );

    $conf_decimal  = $confidence / 100.0;
    $sms_sent_flag = $sms_success ? 1 : 0;

    $stmt->bind_param("isdi", $op_id, $location, $conf_decimal, $sms_sent_flag);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("send_alert.php DB error: " . $e->getMessage());
}

$responseData = [
    'success'   => true,
    'sms_sent'  => $sms_success,
    'message'   => $message,
    'http_code' => $httpCode,
];

if (!$sms_success) {
    $responseData['error_detail'] = $smsResult['message'] ?? $curlError ?? 'Unknown error';
}

echo json_encode($responseData);