<?php
header("Content-Type: application/json");

// -------------------------------
// 1. Square API credentials
// -------------------------------
$accessToken = "EAAAl4t1IxaAmz6YxxT8UAcNpIU2Y_fhmj-eooVFxQtEgY2OtsesFCU40X8Rvtc3"; // SECRET — do Square Dashboard
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

// -------------------------------
// 3. Define plan prices (in cents)
// -------------------------------
$prices = [
    "free"       => 0,
    "starter"    => 499,   // $4.99
    "pro"        => 999,   // $9.99
    "enterprise" => 1999   // $19.99
];

if (!isset($prices[$plan])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid plan."
    ]);
    exit;
}

$amount = $prices[$plan];

// -------------------------------
// 4. Create payment request
// -------------------------------
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
    "idempotency_key" => uniqid() // prevents duplicate charges
];

// -------------------------------
// 5. Send request to Square
// -------------------------------
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

// -------------------------------
// 6. Handle Square response
// -------------------------------
if (isset($result["payment"]["status"]) && $result["payment"]["status"] === "COMPLETED") {

    // Payment successful — webhook will finish the process
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
