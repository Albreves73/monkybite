<?php

$config = include "config.php";

$email = $_GET["email"] ?? "";

if (!$email) {
    die("Missing email.");
}

$url = $config["nextcloud_host"] . "/ocs/v2.php/apps/password_reset/api/v1/reset";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["email" => $email]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config["admin_user"] . ":" . $config["admin_pass"]);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);

$response = curl_exec($ch);
curl_close($ch);

echo "If the email exists, a reset link has been sent.";

?>
