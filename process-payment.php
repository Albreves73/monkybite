<?php
header("Content-Type: application/json");

$accessToken = "COLOQUE_SEU_SQUARE_ACCESS_TOKEN_AQUI";
$locationId  = "LTZ1WY5B11Q9Q";

$NEXTCLOUD_BASE_URL = "https://cloud.monkybite.com";
$ADMIN_USER = "admin";
$ADMIN_PASS = "COLOQUE_SUA_SENHA_NEXTCLOUD_AQUI";

$token = $_POST['token'] ?? null;
$email = $_POST['email'] ?? null;
$plan  = $_POST['plan'] ?? null;

if (!$token || !$email || !$plan) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

$prices = [
    "starter"    => 499,
    "pro"        => 999,
    "premium"    => 1499
];

$quotas = [
    "starter"    => "1 TB",
    "pro"        => "2 TB",
    "premium"    => "5 TB"
];

if (!isset($prices[$plan])) {
    echo json_encode(["success" => false, "message" => "Invalid plan."]);
    exit;
}

$amount = $prices[$plan];
$quota = $quotas[$plan];

$body = [
    "source_id" => $token,
    "amount_money" => [
        "amount" => $amount,
        "currency" => "USD"
    ],
    "location_id" => $locationId,
    "autocomplete" => true,
    "buyer_email_address" => $email,
    "note" => "MonkyBite Subscription - $plan",
    "idempotency_key" => uniqid()
];

$ch = curl_init("https://connect.squareup.com/v2/payments");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Square-Version: 2023-08-16",
    "Authorization: Bearer $accessToken",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

file_put_contents("/var/www/monkybite/square-debug.log", "HTTP_CODE: $httpCode\nCURL_ERROR: $curlErr\nRESPONSE:\n$response\n\n", FILE_APPEND);

$result = json_decode($response, true);

if ($httpCode !== 200 || !$result) {
    echo json_encode([
        "success" => false,
        "message" => "Square returned HTTP $httpCode" . ($curlErr ? ": $curlErr" : "") . ". Check square-debug.log."
    ]);
    exit;
}

if (!isset($result["payment"]["status"]) || $result["payment"]["status"] !== "COMPLETED") {
    $errorMsg = "Payment failed.";
    if (isset($result["errors"][0]["detail"])) {
        $errorMsg = $result["errors"][0]["detail"];
    }
    echo json_encode(["success" => false, "message" => $errorMsg]);
    exit;
}

$userid = rawurlencode($email);
$endpoint = rtrim($NEXTCLOUD_BASE_URL, '/') . "/ocs/v1.php/cloud/users/$userid";

$postFields = http_build_query([
    "quota" => $quota
]);

$ch2 = curl_init($endpoint);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $ADMIN_USER . ':' . $ADMIN_PASS,
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_HTTPHEADER => [
        'OCS-APIRequest: true',
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/xml'
    ],
    CURLOPT_TIMEOUT => 15
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curlErr2 = curl_error($ch2);
curl_close($ch2);

file_put_contents("/var/www/monkybite/nextcloud-debug.log", "HTTP_CODE: $httpCode2\nCURL_ERROR: $curlErr2\nRESPONSE:\n$response2\n\n", FILE_APPEND);

libxml_use_internal_errors(true);
$xml = simplexml_load_string($response2);

if ($xml === false) {
    echo json_encode([
        "success" => false,
        "message" => "Payment completed, but Nextcloud update failed. Check nextcloud-debug.log."
    ]);
    exit;
}

$statuscode = (string)($xml->meta->statuscode ?? '');
if ($statuscode !== '100') {
    $message = (string)($xml->meta->message ?? 'Unknown error');
    echo json_encode([
        "success" => false,
        "message" => "Payment completed, but Nextcloud quota update failed: " . $message
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "redirect" => "payment-success.html"
]);
?>
