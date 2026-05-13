<?php
header('Content-Type: application/json; charset=utf-8');

$NEXTCLOUD_BASE_URL = "https://cloud.monkybite.com"; // seu Nextcloud
$ADMIN_USER = "admin"; // mantenha seguro
$ADMIN_PASS = "Cu214200@@$"; // substitua com a senha real

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
echo json_encode(['exists' => false]);
exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
echo json_encode(['exists' => false]);
exit;
}

// use email as userid (se for seu caso). Se você usa outro userid, ajuste aqui.
$userid = $email;

// endpoint de verificação de usuário (Provisioning API)
$endpoint = rtrim($NEXTCLOUD_BASE_URL, '/') . '/ocs/v1.php/cloud/users/' . rawurlencode($userid);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
CURLOPT_RETURNTRANSFER => true,
CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
CURLOPT_USERPWD => $ADMIN_USER . ':' . $ADMIN_PASS,
CURLOPT_HTTPHEADER => [
'OCS-APIRequest: true',
'Accept: application/xml'
],
CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

// Se a requisição falhou (timeout/erro) consideramos que não existe para não bloquear cadastro indevidamente.
// Se desejar, logue o erro no servidor para depuração.
if ($response === false || $httpCode >= 400) {
// opcional: error_log("check-email.php curl error: $curlErr / httpCode $httpCode");
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
