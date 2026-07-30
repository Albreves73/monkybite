<?php
// =======================================
// MonkyBite — PAYMENT SUCCESS HANDLER
// =======================================

// Recebe dados enviados pelo checkout
$plan  = $_GET['plan'] ?? 'free';
$email = $_GET['email'] ?? '';

if (!$email) {
    die("Missing email.");
}

// Credenciais do Nextcloud
$nextcloud_url = "http://localhost/ocs/v1.php/cloud/users";
$admin_user    = "admin";
$admin_pass    = "YOUR_ADMIN_PASSWORD";

// =======================================
// 1. Definir quota e grupo baseado no plano
// =======================================

$quota = "5 GB";
$group = "free";

switch ($plan) {
    case "starter":
        $quota = "1 TB";
        $group = "starter";
        break;

    case "pro":
        $quota = "2 TB";
        $group = "pro";
        break;

    case "enterprise":
        $quota = "5 TB";
        $group = "enterprise";
        break;

    case "free":
    default:
        $quota = "5 GB";
        $group = "free";
        break;
}

// =======================================
// 2. Aplicar quota real no Nextcloud
// =======================================

$quota_url = $nextcloud_url . "/" . urlencode($email);

$ch = curl_init($quota_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["quota" => $quota]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin_user:$admin_pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_exec($ch);
curl_close($ch);

// =======================================
// 3. Adicionar usuário ao grupo correto
// =======================================

$group_url = "http://localhost/ocs/v1.php/cloud/users/" . urlencode($email) . "/groups";

$ch = curl_init($group_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["groupid" => $group]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$admin_user:$admin_pass");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["OCS-APIRequest: true"]);
curl_exec($ch);
curl_close($ch);

// =======================================
// 4. Redirecionar para congratulations
// =======================================

header("Location: /congratulations.html?plan=" . urlencode($plan));
exit;

?>
