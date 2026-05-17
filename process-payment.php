<?php
header("Content-Type: application/json");

$accessToken = "EAAAlz_CU24QwkuDeXtJQQ6zg1qRviQZ2ESc7kLDmm1hHP3hPCOrC9qEp2TL4pYw"; // ← certifique-se de manter o token que você gerou
$locationId  = "LTZ1WY5B11Q9Q";

$token = $_POST['token'] ?? null;
$email = $_POST['email'] ?? null;
$plan  = $_POST['plan'] ?? null;

if (!$token || !$email || !$plan) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

$prices = [
    "free"       => 0,
    "starter"    => 499,
    "pro"        => 999,
    "enterprise" => 1999
];

if (!isset($prices[$plan])) {
    echo json_encode(["success" => false, "message" => "Invalid plan."]);
    exit;
}

$amount = $prices[$plan];

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

// === DEBUG: gravar resposta da Square ===
file_put_contents("square-debug.log", "HTTP_CODE: $httpCode\nCURL_ERROR: $curlErr\nRESPONSE:\n$response\n\n", FILE_APPEND);
// =========================================

$result = json_decode($response, true);

if ($httpCode !== 200 || !$result) {
    echo json_encode([
        "success" => false,
        "message" => "Square returned HTTP $httpCode" . ($curlErr ? ": $curlErr" : "") . ". Check square-debug.log."
    ]);
    exit;
}

if (!$result) {
    echo json_encode(["success" => false, "message" => "Invalid JSON from Square. Check square-debug.log."]);
    exit;
}

if (isset($result["payment"]) && isset($result["payment"]["status"]) && $result["payment"]["status"] === "COMPLETED") {
    echo json_encode([
        "success" => true,
        "redirect" => "payment-success.html"
    ]);
    exit;
}

$errorMsg = "Payment failed.";
if (isset($result["errors"]) && is_array($result["errors"]) && count($result["errors"]) > 0) {
    $errorMsg = $result["errors"][0]["detail"] ?? $result["errors"][0]["category"] ?? "Payment failed.";
}

echo json_encode([
    "success" => false,
    "message" => $errorMsg
]);
?>
