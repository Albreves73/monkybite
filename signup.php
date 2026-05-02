<?php
session_start();

// Enable error reporting (optional)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Collect POST fields
$email     = trim($_POST['email'] ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$password  = $_POST['password'] ?? '';
$plan      = $_POST['plan'] ?? 'free';

// Basic validations
if ($email === '' || $firstName === '' || $lastName === '' || $password === '') {
    die("Missing required fields.");
}

// Password validations
if (strlen($password) < 10) {
    die("Password must be at least 10 characters long.");
}
if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    die("Password must contain both letters and numbers.");
}

// 🔷 Save user data in session (temporary until payment)
$_SESSION['pending_user'] = [
    'email'     => $email,
    'firstName' => $firstName,
    'lastName'  => $lastName,
    'password'  => $password,
    'plan'      => $plan
];

// 🔷 Redirect to Square checkout based on plan
switch ($plan) {
    case 'starter':
        header("Location: https://square.link/u/ObSxOe10");
        exit;

    case 'pro':
        header("Location: https://square.link/u/zLUQxpol");
        exit;

    case 'enterprise':
        header("Location: https://square.link/u/mwyQZlNb");
        exit;

    case 'free':
    default:
        // Free plan → no payment → go directly to success page
        header("Location: payment-success.php?free=1");
        exit;
}
?>
