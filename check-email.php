<?php
header('Content-Type: application/json; charset=utf-8');

$NEXTCLOUD_BASE_URL = "https://cloud.monkybite.com";
$ADMIN_USER = "admin";
$ADMIN_PASS = "Cu214200@@$";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exists' => false]);
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    echo json_encode(['exists' => false]);
    exit;
}

$userid = rawurlencode($email);
$endpoint = rtrim($NEXTCLOUD_BASE_URL, '/') . "/ocs/v1.php/cloud/users/$userid";

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $ADMIN_USER . ':' . $ADMIN_PASS,
    CURLOPT_HTTPHEADER => [
        'OCS-APIRequest: true',
        'Accept: application/xml'
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    echo json_encode(['exists' => false]);
    exit;
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($response);

if ($xml === false) {
    echo json_encode(['exists' => false]);
    exit;
}

$statuscode = (string)($xml->meta->statuscode ?? '');
echo json_encode(['exists' => $statuscode === '100']);
?>
