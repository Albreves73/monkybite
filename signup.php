<?php
// =======================================
// MonkyBite — SIGNUP (FINAL)
// =======================================

$config = include "config.php";

$name     = $_POST['name'] ?? '';
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$plan     = $_POST['plan'] ?? 'free';

if (!$name || !$email || !$password) {
    die("Missing required fields.");
}

// =======================================
// 1. Verificar se email já existe (via check-email.php)
// =======================================

$check = file_get_contents("check-email.php?email=" . urlencode($email));

if ($check === "exists") {
    die("Email already exists.");
}

// =======================================
// 2. Redirecionar para checkout
// =======================================

header("Location: /checkout.html?plan=" . urlencode($plan) . "&email=" . urlencode($email) . "&name=" . urlencode($name));
exit;

?>
