<?php
// Enable error reporting (useful during setup; you can disable later)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Replace with your Nextcloud details
$NEXTCLOUD_BASE_URL = "https://YOUR_NEXTCLOUD_DOMAIN"; // e.g., https://cloud.monkybite.com
$ADMIN_USER = "admin";
$ADMIN_PASS = "YOUR_ADMIN_PASSWORD";

// Helper: send an error and stop
function fail($message, $httpCode = 400) {
    http_response_code($httpCode);
    echo "<h1>Sign Up Error</h1><p>$message</p>";
    exit;
}

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail("Invalid request method.");
}

// Collect POST fields
$email     = trim($_POST['email'] ?? '');
$firstName = trim($_POST['first-name'] ?? '');
$lastName  = trim($_POST['last-name'] ?? '');
$password  = $_POST['password'] ?? '';
$plan      = $_POST['plan'] ?? 'free';

// Basic validations
if ($email === '' || $firstName === '' || $lastName === '' || $password === '') {
    fail("Missing required fields.");
}

// Password validations
if (strlen($password) < 10) {
    fail("Password must be at least 10 characters long.");
}
if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    fail("Password must contain both letters and numbers.");
}

// Prepare Nextcloud OCS API request
$displayName = $firstName . " " . $lastName;
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
    fail("Server connection error: " . htmlspecialchars($curlErr));
}

// Parse OCS XML response to read statuscode
libxml_use_internal_errors(true);
$xml = simplexml_load_string($response);
if ($xml === false) {
    // Fallback if XML parsing fails
    fail("Unexpected response from server (not XML). Code: $httpCode");
}
$statuscode = (string)($xml->meta->statuscode ?? '');

// Handle common outcomes
if ($statuscode !== '100') {
    // 100 = OK; other common codes: 102 (user exists), 107 (invalid password)
    $status = (string)($xml->meta->status ?? 'error');
    $message = (string)($xml->meta->message ?? 'Unknown error');
    fail("Nextcloud returned $status (code $statuscode): " . htmlspecialchars($message), 400);
}

// Success: redirect based on plan
if (in_array(strtolower($plan), ['starter', 'pro', 'enterprise'])) {
    header("Location: billing.html");
    exit;
} else {
    header("Location: dashboard.html");
    exit;
}
