<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require 'vendor/autoload.php';

use Square\SquareClient;
use Square\Environment;
use Square\Models\Money;
use Square\Models\CreatePaymentLinkRequest;

$accessToken = 'EAAAl4tIxaAmz6YxxT8XXXXXXXXXXXXXXXXXXXXXXXX2OtsesFCU40X8Rvtc3';
$locationId  = 'sq0idp-JHruqkfGcQdQfmgDQYjnUQ';

// 1. Pega dados do formulário
$email     = $_POST['email']      ?? null;
$firstName = $_POST['firstName']  ?? null;
$lastName  = $_POST['lastName']   ?? null;
$password  = $_POST['password']   ?? null;
$plan      = $_POST['plan']       ?? 'starter';

// Validação básica
if (!$email || !$firstName || !$lastName || !$password) {
    die('Missing required fields.');
}

// 2. Guarda dados na sessão
$_SESSION['pending_user'] = [
    'email'     => $email,
    'firstName' => $firstName,
    'lastName'  => $lastName,
    'password'  => $password,
    'plan'      => $plan,
];

// 3. Define valor do plano (em cents)
switch ($plan) {
    case 'starter':
        $amount = 99;
        break;
    case 'pro':
        $amount = 999;
        break;
    default:
        $amount = 99;
}

// 4. Configura cliente Square
$client = new SquareClient([
    'accessToken' => $accessToken,
    'environment' => Environment::PRODUCTION,
]);

$paymentLinksApi = $client->getPaymentLinksApi();

// 5. Monta o pedido
$money = new Money();
$money->setAmount($amount);
$money->setCurrency('USD');

$body = new CreatePaymentLinkRequest([
    'idempotency_id' => uniqid('mb_', true),
    'quick_pay' => [
        'name'        => 'MonkyBite ' . ucfirst($plan) . ' Plan',
        'price_money' => $money,
        'location_id' => $locationId,
    ],
    'checkout_options' => [
        'redirect_url' => 'https://monkybite.com/payment-success.php',
    ],
]);

// 6. Cria o link de pagamento
$response = $paymentLinksApi->createPaymentLink($body);

if ($response->isSuccess()) {
    $result = $response->getResult();
    $url = $result->getPaymentLink()->getUrl();
    header("Location: " . $url);
    exit;
} else {
    $errors = $response->getErrors();
    echo "Error creating checkout:<br>";
    foreach ($errors as $error) {
        echo htmlspecialchars($error->getDetail()) . "<br>";
    }
}
