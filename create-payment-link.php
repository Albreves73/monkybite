<?php
require 'square-config.php';

// ===============================
//  CONEXÃO COM O BANCO
// ===============================
$pdo = new PDO("mysql:host=localhost;dbname=monkybite;charset=utf8", "root", "");

// ===============================
//  RECEBE user_id
// ===============================
$userId = intval($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    die("Invalid user ID");
}

// ===============================
//  BUSCA USUÁRIO NO BANCO
// ===============================
$stmt = $pdo->prepare("SELECT email, plan FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

$email = $user['email'];
$plan  = $user['plan'];

// Preço em centavos (vem do square-config.php)
$amount = $PLAN_PRICES[$plan] ?? 0;

// ===============================
//  MONTA PAYLOAD DO PAYMENT LINK
// ===============================
$body = [
    "idempotency_key" => uniqid("mb_", true),
    "quick_pay" => [
        "name"        => "MonkyBite " . ucfirst($plan) . " Plan",
        "price_money" => [
            "amount"   => $amount,
            "currency" => "USD"
        ],
        "location_id" => $SQUARE_LOCATION_ID
    ],
    "checkout_options" => [
        "redirect_url" => "https://monkybite.com/payment-success.php"
    ],
    "reference_id" => (string)$userId
];

// ===============================
//  ENVIA PARA A API DA SQUARE
// ===============================
$ch = curl_init("https://connect.squareup.com/v2/online-checkout/payment-links");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Square-Version: 2023-08-16",
    "Authorization: Bearer $SQUARE_ACCESS_TOKEN",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// ===============================
//  REDIRECIONA PARA O LINK DE PAGAMENTO
// ===============================
if (isset($result["payment_link"]["url"])) {
    header("Location: " . $result["payment_link"]["url"]);
    exit;
}

// ===============================
//  SE DER ERRO, MOSTRA O RETORNO
// ===============================
echo "Erro ao criar Payment Link:<br><br>";
echo "<pre>";
print_r($result);
echo "</pre>";
?>
