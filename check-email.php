<?php

if (!isset($_POST['email'])) {
    echo json_encode(['exists' => false]);
    exit;
}

$email = trim($_POST['email']);
$username = $email;

$adminUser = "admin";
$adminPass = "Cu214200@@$"; // ← sua senha real

$checkUser = curl_init();
curl_setopt($checkUser, CURLOPT_URL, "https://cloud.monkybite.com/ocs/v1.php/cloud/users/$username");
curl_setopt($checkUser, CURLOPT_RETURNTRANSFER, true);
curl_setopt($checkUser, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_setopt($checkUser, CURLOPT_USERPWD, "$adminUser:$adminPass");

$response = curl_exec($checkUser);
curl_close($checkUser);

if (strpos($response, '<statuscode>100</statuscode>') !== false) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}
