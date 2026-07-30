<?php
// =======================================
// MonkyBite — PROCESS PAYMENT (Square)
// =======================================

// Recebe JSON do checkout
$data = json_decode(file_get_contents("php://input"), true);

$token = $data["token"] ?? null;
$plan  = $data["plan"] ?? null;
$email = $data["email"] ?? null;

if (!$token || !$plan || !$email) {
    echo json_encode(["success" => false, "error" => "Missing fields"]);
    exit;
}

// =======================================
// 1. Definir valor baseado no plano
// Valores em centavos (Square usa integer)
// =======================================

$amount = 0;

switch ($plan) {
    case "starter":
        $amount = 499;   // $4.99
        break;

    case "pro":
        $amount = 999;   // $9.99
        break;

    case "enterprise":
        $amount = 1999;  // $19.99
        break;

    case "free":
    default:
        echo json_encode(["success" => false, "error" => "Free plan has no payment"]);
        exit;
}

// =======================================
// 2. Square API (produção)
// =======================================

$SQUARE_ACCESS_TOKEN = "PRODUCTION_ACCESS_TOKEN_AQUI";  // 🔥 Coloque seu token real
$SQUARE_LOCATION_ID  = "PRODUCTION_LOCATION_ID_AQUI";   // 🔥 Coloque seu location real

$payload = [
    "idempotency_key" => uniqid(),
    "amount_money" => [
        "amount" => $amount,
        "currency" => "USD"
    ],
    "source_id" => $token,
    "location_id" => $SQUARE_LOCATION_ID,
    "note" => "MonkyBite subscription for $email ($plan)"
];

$ch = curl_init("https://connect.squareup.com/v2/payments");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $SQUARE_ACCESS_TOKEN"
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// =======================================
// 3. Verificar se o pagamento foi aprovado
// =======================================

if (isset($result["payment"]["status"]) && $result["payment"]["status"] === "COMPLETED") {
    echo json_encode(["success" => true]);
    exit;
} else {
    echo json_encode([
        "success" => false,
        "error" => $result["errors"][0]["detail"] ?? "Payment failed"
    ]);
    exit;
}

?>

