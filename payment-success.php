<?php
session_start();

// Enable error reporting (opcional, pode tirar depois)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔷 CONFIGURAÇÕES DO NEXTCLOUD
$NEXTCLOUD_BASE_URL = "https://cloud.monkybite.com";
$ADMIN_USER = "admin";
$ADMIN_PASS = "Cu214200@@$"; // mesmo que você usava no signup.php antigo

// Se não houver dados de usuário na sessão, não tem o que fazer
if (!isset($_SESSION['pending_user'])) {
    die("No pending user data found. Please contact support.");
}

$user = $_SESSION['pending_user'];
$email     = $user['email'];
$firstName = $user['firstName'];
$lastName  = $user['lastName'];
$password  = $user['password'];
$plan      = $user['plan'] ?? 'free';

$displayName = $firstName . " " . $lastName;

// 🔷 SE VOCÊ QUISER, AQUI VOCÊ PODE VALIDAR SE O PAGAMENTO FOI REALMENTE APROVADO
// Por enquanto vamos assumir que se chegou aqui, o Square já aprovou.

// 🔷 CRIAR USUÁRIO NO NEXTCLOUD VIA OCS API
$endpoint = rtrim($NEXTCLOUD_BASE_URL, '/') . "/ocs/v1.php/cloud/users";

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_USERPWD        => $ADMIN_USER . ":" . $ADMIN_PASS,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'userid'      => $email,
        'password'    => $password,
        'displayName' => $displayName
    ],
    CURLOPT_HTTPHEADER     => ["OCS-APIRequest: true"],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    die("Server connection error: " . htmlspecialchars($curlErr));
}

// Parse OCS XML response
libxml_use_internal_errors(true);
$xml = simplexml_load_string($response);
if ($xml === false) {
    die("Unexpected response from server (not XML). Code: $httpCode");
}

$statuscode = (string)($xml->meta->statuscode ?? '');

if ($statuscode !== '100') {
    $status  = (string)($xml->meta->status ?? 'error');
    $message = (string)($xml->meta->message ?? 'Unknown error');
    die("Nextcloud returned $status (code $statuscode): " . htmlspecialchars($message));
}

// 🔷 SE CHEGOU AQUI, USUÁRIO FOI CRIADO COM SUCESSO

// Opcional: você pode limpar os dados da sessão
unset($_SESSION['pending_user']);

// 🔷 REDIRECIONAR PARA O NEXTCLOUD (LOGIN MANUAL POR ENQUANTO)
header("Location: " . $NEXTCLOUD_BASE_URL);
exit;
