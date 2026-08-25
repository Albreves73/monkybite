<?php
// =======================================
// MonkyBite — PAYMENT SUCCESS (FINAL)
// =======================================

$config = include "config.php";

$nextcloud = $config["nextcloud_host"] . "/ocs/v1.php/cloud/users";
$admin     = $config["admin_user"];
$pass      = $config["admin_pass"];

$plan  = $_GET["plan"] ?? "free";
$email = $_GET["email"] ?? "";
$name  = $_GET["name"] ?? "";
$passw = $_GET["password"] ?? "";

if (!$email || !$name || !$passw) {
    die("Missing fields.");
}

// =======================================
// 1. Criar usuário no Nextcloud
// =======================================

$data = [
    "userid"      => $email,
    "password"    => $passw,
    "displayName" => $name
];

$ch = curl_init($nextcloud);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin:$pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_exec($ch);
curl_close($ch);

// =======================================
// 2. Aplicar quota e grupo real
// =======================================

$quota = $config["plans"][$plan]["quota"];
$group = $config["plans"][$plan]["group"];

// QUOTA
$quota_url = $nextcloud . "/" . urlencode($email);

$ch = curl_init($quota_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["quota" => $quota]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin:$pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_exec($ch);
curl_close($ch);

// GROUP
$group_url = $nextcloud . "/" . urlencode($email) . "/groups";

$ch = curl_init($group_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["groupid" => $group]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin:$pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_exec($ch);
curl_close($ch);

// =======================================
// 3. Redirecionar
// =======================================

header("Location: /congratulations.html?plan=" . urlencode($plan));
exit;

?>

