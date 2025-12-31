<?php

// Permite resposta JSON
header('Content-Type: application/json');

// Verifica se os campos obrigatórios vieram
if (!isset($_POST['email'], $_POST['firstName'], $_POST['lastName'], $_POST['password'], $_POST['plan'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Dados do formulário
$email = trim($_POST['email']);
$firstName = trim($_POST['firstName']);
$lastName = trim($_POST['lastName']);
$password = $_POST['password'];
$plan = ucfirst(strtolower($_POST['plan'])); // free → Free

// Username será o email completo
$username = $email;

// URL base do Nextcloud
$nextcloudUrl = "https://cloud.monkybite.com/ocs/v1.php/cloud/users";

// Credenciais do admin do Nextcloud
$adminUser = "admin";
$adminPass = "Cu214200@@$"; // <-- coloque sua senha real aqui

// -----------------------------
// ✅ Verificar se o usuário já existe no Nextcloud
// -----------------------------
$checkUser = curl_init();
curl_setopt($checkUser, CURLOPT_URL, "https://cloud.monkybite.com/ocs/v1.php/cloud/users/$username");
curl_setopt($checkUser, CURLOPT_RETURNTRANSFER, true);
curl_setopt($checkUser, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_setopt($checkUser, CURLOPT_USERPWD, "$adminUser:$adminPass");

$responseCheck = curl_exec($checkUser);
curl_close($checkUser);

// Se o usuário já existe, Nextcloud retorna statuscode 100
if (strpos($responseCheck, '<statuscode>100</statuscode>') !== false) {
    echo "<script>alert('This email is already registered. Please use another one.'); window.history.back();</script>";
    exit;
}


// -----------------------------// ✅ 1. Criar o usuário no Nextcloud
// -----------------------------
$createUser = curl_init();
curl_setopt($createUser, CURLOPT_URL, $nextcloudUrl);
curl_setopt($createUser, CURLOPT_RETURNTRANSFER, true);
curl_setopt($createUser, CURLOPT_POST, true);
curl_setopt($createUser, CURLOPT_POSTFIELDS, http_build_query([
    'userid' => $username,
    'password' => $password,
    'email' => $email,
    'displayname' => $firstName . " " . $lastName
]));
curl_setopt($createUser, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_setopt($createUser, CURLOPT_USERPWD, "$adminUser:$adminPass");

$responseUser = curl_exec($createUser);
curl_close($createUser);

// Verifica se houve erro na criação
if (strpos($responseUser, '<statuscode>') !== false && !strpos($responseUser, '<statuscode>100</statuscode>')) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create user', 'debug' => $responseUser]);
    exit;
}

// -----------------------------
// ✅ 2. Adicionar o usuário ao grupo correto
// -----------------------------
$addGroup = curl_init();
curl_setopt($addGroup, CURLOPT_URL, "https://cloud.monkybite.com/ocs/v1.php/cloud/users/$username/groups");
curl_setopt($addGroup, CURLOPT_RETURNTRANSFER, true);
curl_setopt($addGroup, CURLOPT_POST, true);
curl_setopt($addGroup, CURLOPT_POSTFIELDS, http_build_query(['groupid' => $plan]));
curl_setopt($addGroup, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_setopt($addGroup, CURLOPT_USERPWD, "$adminUser:$adminPass");

$responseGroup = curl_exec($addGroup);
curl_close($addGroup);

// -----------------------------
// ✅ 3. Resposta final
// -----------------------------

header("Location: https://cloud.monkybite.com/index.php/login");
exit;

