<?php

$config = include "config.php";

$nextcloud = $config["nextcloud_host"] . "/ocs/v1.php/cloud/users";
$admin     = $config["admin_user"];
$pass      = $config["admin_pass"];

$email = $_GET["email"] ?? "";

if (!$email) {
    die("missing");
}

$url = $nextcloud . "?search=" . urlencode($email);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin:$pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);

$response = curl_exec($ch);
curl_close($ch);

if (strpos($response, $email) !== false) {
    echo "exists";
} else {
    echo "ok";
}

?>
