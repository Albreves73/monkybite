<?php
// Load configuration
$config = include(__DIR__ . '/config.php');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Read form data
$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$plan     = isset($_POST['plan']) ? $_POST['plan'] : '';

// Basic validations
if ($email === '' || $password === '' || $plan === '') {
    http_response_code(400);
    exit('Missing data: email, password and plan are required.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Invalid email.');
}
if (!isset($config['plans'][$plan])) {
    http_response_code(400);
    exit('Invalid plan.');
}

$quota = $config['plans'][$plan]['quota'];
$group = $config['plans'][$plan]['group'];

// OCS endpoint for user creation in Nextcloud
$url = rtrim($config['nextcloud_host'], '/') . '/ocs/v1.php/cloud/users';

// Build OCS payload (form-encoded)
$data = [
  'userid'   => $email,
  'password' => $password,
  'quota'    => $quota
];

// Call API
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'OCS-APIRequest: true',
  'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_USERPWD, $config['admin_user'] . ':' . $config['admin_pass']);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    exit('cURL error: ' . $error);
}

// Try to add the user to the selected plan group (optional, recommended)
if ($httpcode === 200) {
    $groupUrl = rtrim($config['nextcloud_host'], '/') . '/ocs/v1.php/cloud/users/' . rawurlencode($email) . '/groups';
    $groupData = ['group' => $group];

    $ch2 = curl_init($groupUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($groupData));
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
      'OCS-APIRequest: true',
      'Accept: application/json'
    ]);
    curl_setopt($ch2, CURLOPT_USERPWD, $config['admin_user'] . ':' . $config['admin_pass']);
    $response2 = curl_exec($ch2);
    $httpcode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($httpcode2 !== 200) {
        // Do not fail the signup because of group issue; just inform
        echo "Account created, but failed to add to group {$group}. HTTP code: {$httpcode2}.<br>";
    }
}

// Response to user
if ($httpcode === 200) {
    echo "<!DOCTYPE html>
    <html>
    <head>
      <meta http-equiv='refresh' content='3;url=" . htmlspecialchars($config['nextcloud_host']) . "/index.php/login'>

      <title>Account Created</title>
    </head>
    <body>
      <h2>Your account has been created successfully.</h2>
      <p>You will be redirected to your cloud shortly...</p>
    </body>
    </html>";
} else {
    // Show code and raw payload for debugging
    http_response_code(400);
    echo "<!DOCTYPE html>
    <html>
    <head><title>Error</title></head>
    <body>
      <h2>Error creating account.</h2>
      <p>HTTP code: {$httpcode}</p>
      <pre>" . htmlspecialchars($response) . "</pre>
    </body>
    </html>";
}

