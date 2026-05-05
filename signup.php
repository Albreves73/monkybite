<?php
session_start();
require 'square-config.php';

// ===============================
//  CONEXÃO COM O BANCO
// ===============================
$pdo = new PDO("mysql:host=localhost;dbname=monkybite;charset=utf8", "root", "");

// ===============================
//  COLETA DOS CAMPOS DO FORM
// ===============================
$email     = trim($_POST['email'] ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$password  = $_POST['password'] ?? '';
$plan      = $_POST['plan'] ?? 'free';

// ===============================
//  VALIDAÇÕES
// ===============================
if ($email === '' || $firstName === '' || $lastName === '' || $password === '') {
    die("Missing required fields.");
}

if (strlen($password) < 10) {
    die("Password must be at least 10 characters long.");
}

if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    die("Password must contain both letters and numbers.");
}

// ===============================
//  HASH DA SENHA
// ===============================
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// ===============================
//  SALVA NO BANCO (STATUS = pending)
// ===============================
$stmt = $pdo->prepare("
    INSERT INTO users (email, firstName, lastName, password, plan, status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$stmt->execute([$email, $firstName, $lastName, $passwordHash, $plan]);

// ID do usuário recém-criado
$userId = $pdo->lastInsertId();

// ===============================
//  REDIRECIONA PARA CRIAR PAYMENT LINK
// ===============================
header("Location: create-payment-link.php?user_id=" . $userId);
exit;
?>
