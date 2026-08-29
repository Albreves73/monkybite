<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = include "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$token    = $data["token"]    ?? null;
$plan     = $data["plan"]     ?? null;
$email    = $data["email"]    ?? null;
$name     = $data["name"]     ?? null;
$password = $data["password"] ?? null;
$method   = $data["method"]   ?? "card";

if (!$token || !$plan || !$email) {
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit;
}

// =======================================
// PLANOS E VALORES
// =======================================

$amounts = [
    "starter"    => 499,
    "pro"        => 999,
    "enterprise" => 1999
];

if (!isset($amounts[$plan])) {
    echo json_encode(["success" => false, "error" => "Invalid plan"]);
    exit;
}

$amount = $amounts[$plan];

// =======================================
// PAGAMENTO VIA PAYPAL
// =======================================

if ($method === "paypal") {

    // Aqui você pode validar com Webhook do PayPal futuramente.
    // Por enquanto, consideramos aprovado.

    // Aqui é onde você vai criar o usuário no Nextcloud depois.
    // Exemplo:
    // create_nextcloud_user($name, $email, $password, $plan);

    echo json_encode(["success" => true]);
    exit;
}

// =======================================
// PAGAMENTO VIA SQUARE (cartão / apple / google)
// =======================================

$payload = [
    "idempotency_key" => uniqid(),
    "amount_money" => [
        "amount"   => $amount,
        "currency" => "USD"
    ],
    "source_id"   => $token,
    "location_id" => $config["square_location_id"],
    "note"        => "MonkyBite subscription for $email ($plan) via $method"
];

$ch = curl_init("https://connect.squareup.com/v2/payments");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $config["square_access_token"]
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// =======================================
// VERIFICAR PAGAMENTO SQUARE
// =======================================

if (isset($result["payment"]["status"]) && $result["payment"]["status"] === "COMPLETED") {

    // Aqui você vai criar o usuário no Nextcloud depois.
    // Exemplo:
    // create_nextcloud_user($name, $email, $password, $plan);

    echo json_encode(["success" => true]);
    exit;
}

// =======================================
// ERRO NO PAGAMENTO
// =======================================

echo json_encode([
    "success" => false,
    "error" => $result["errors"][0]["detail"] ?? "Payment failed"
]);
exit;

?>
