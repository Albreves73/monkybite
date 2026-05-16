<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$NEXTCLOUD_BASE_URL = "https://cloud.monkybite.com";
$ADMIN_USER = "admin";
$ADMIN_PASS = "Cu214200@@$";

function fail($message, $httpCode = 400) {
    http_response_code($httpCode);
    echo "<h1>Sign Up Error</h1><p>" . htmlspecialchars($message) . "</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail("Invalid request method.");
}

$email      = trim($_POST['email'] ?? '');
$firstName  = trim($_POST['firstName'] ?? '');
$lastName   = trim($_POST['lastName'] ?? '');
$password   = $_POST['password'] ?? '';
$plan       = trim($_POST['plan'] ?? 'free');

if ($email === '' || $firstName === '' || $lastName === '' || $password === '') {
    fail("Missing required fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail("Invalid email address.");
}

if (strlen($password) < 10) {
    fail("Password must be at least 10 characters long.");
}

if (
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
) {
    fail("Password must contain uppercase, lowercase, number and special character.");
}

$displayName = $firstName . ' ' . $lastName;
$userid = $email;

$endpoint = rtrim($NEXTCLOUD_BASE_URL, '/') . '/ocs/v1.php/cloud/users';

$postFields = http_build_query([
    'userid'      => $userid,
    'password'    => $password,
    'displayName' => $displayName
]);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => $ADMIN_USER . ':' . $ADMIN_PASS,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_HTTPHEADER     => [
        'OCS-APIRequest: true',
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/xml'
    ],
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    fail("Server connection error. Please try again later.");
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($response);

if ($xml === false) {
    fail("Unexpected server response. Please contact support.");
}

$statuscode = (string)($xml->meta->statuscode ?? '');
$message = (string)($xml->meta->message ?? 'Unknown error');

if ($statuscode !== '100') {
    if (stripos($message, 'user') !== false || stripos($message, 'exists') !== false) {
        fail("An account with that email already exists.");
    }
    fail("Sign up failed: " . $message);
}

if (in_array(strtolower($plan), ['starter', 'pro', 'enterprise'])) {
    header("Location: billing.html");
    exit;
}

header("Location: dashboard.html");
exit;
?>
