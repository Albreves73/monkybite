<?php
// ===============================
// MonkyBite — SIGNUP BACKEND
// ===============================

// Recebe dados do formulário
$name     = $_POST['name'] ?? '';
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$plan     = $_POST['plan'] ?? 'free';

// Verifica campos obrigatórios
if (!$name || !$email || !$password) {
    die("Missing required fields.");
}

// ===============================
// 1. Verificar se o email já existe
// ===============================

$nextcloud_url = "http://localhost/ocs/v1.php/cloud/users";
$admin_user    = "admin";
$admin_pass    = "YOUR_ADMIN_PASSWORD";

$check_url = $nextcloud_url . "?search=" . urlencode($email);

$ch = curl_init($check_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin_user:$admin_pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);

$response = curl_exec($ch);
curl_close($ch);

if (strpos($response, $email) !== false) {
    die("Email already exists.");
}

// ===============================
// 2. Criar usuário no Nextcloud
// ===============================

$data = [
    "userid"   => $email,
    "password" => $password,
    "displayName" => $name
];

$ch = curl_init($nextcloud_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin_user:$admin_pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);

$response = curl_exec($ch);
curl_close($ch);

// ===============================
// 3. Aplicar plano temporário (free)
//    O plano real será aplicado após o pagamento
// ===============================

$quota = "5 GB"; // plano temporário até pagar

$quota_url = "http://localhost/ocs/v1.php/cloud/users/$email";

$ch = curl_init($quota_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["quota" => $quota]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin_user:$admin_pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);

curl_exec($ch);
curl_close($ch);

// ===============================
// 4. Redirecionar para o checkout
// ===============================

header("Location: /checkout.html?plan=" . urlencode($plan) . "&email=" . urlencode($email));
exit;

?>
