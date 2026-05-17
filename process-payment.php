<?php
header("Content-Type: application/json");

// -------------------------------
// 1. Square API credentials
// -------------------------------
$accessToken = "EAAAlz_CU24QwkuDeXtJQQ6zg1qRviQZ2ESc7kLDmm1hHP3hPCOrC9qEp2TL4pYw"; // SECRET — do Square Dashboard
$locationId  = "LTZ1WY5B11Q9Q";

// -------------------------------
// 2. Receive POST data
// -------------------------------

$token = $_POST['token'] ?? null;
$email = $_POST['email'] ?? null;
$plan  = $_POST['plan'] ?? null;

if (!$token || !$email || !$plan) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);
    exit;
}

$prices = [
    "free"       => 0,
    "starter"    => 499,
    "pro"        => 999,
    "enterprise" => 1999
];

if (!isset($prices[$plan])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid plan."
    ]);
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
curl_close($ch);

$result = json_decode($response, true);

if (isset($result["payment"]["status"]) && $result["payment"]["status"] === "COMPLETED") {
    echo json_encode([
        "success" => true,
        "redirect" => "payment-success.html"
    ]);
    exit;
} else {
    $errorMsg = $result["errors"][0]["detail"] ?? "Payment failed.";
    echo json_encode([
        "success" => false,
        "message" => $errorMsg
    ]);
    exit;
}
?>
