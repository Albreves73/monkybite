<?php

$config = include "config.php";

session_start();

$email = $_SESSION["user"] ?? null;
$password = $_SESSION["pass"] ?? null;

if (!$email || !$password) {
    die(json_encode(["error" => "not_logged"]));
}

$quota_url = $config["nextcloud_host"] . "/remote.php/dav/files/" . $email;

$ch = curl_init($quota_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$email:$password");
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
curl_close($ch);

// Fake values for now — Nextcloud returns quota via PROPFIND
echo json_encode([
    "plan"  => "starter",
    "quota" => "1 TB",
    "used"  => "0 GB"
]);

?>
