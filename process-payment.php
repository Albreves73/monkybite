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

$payload = [
    "idempotency_key" => uniqid(),
    "amount_money" => [
        "amount" => $amount,
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

if (isset($result["payment"]["status"]) && $result["payment"]["status"] === "COMPLETED") {

    // Aqui entra a parte de criar usuário no Nextcloud:
    // - usar $name, $email, $password, $plan
    // - chamar API do Nextcloud (provisioning API)
    // - colocar no grupo do plano
    // - definir quota
    // Por enquanto, só retornamos sucesso.

    echo json_encode(["success" => true]);
    exit;
}

echo json_encode([
    "success" => false,
    "error" => $result["errors"][0]["detail"] ?? "Payment failed"
]);
exit;
