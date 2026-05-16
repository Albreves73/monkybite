<?php
header('Content-Type: application/json; charset=utf-8');

$NEXTCLOUD_BASE_URL = "https://cloud.monkybite.com";
$ADMIN_USER = "admin";
$ADMIN_PASS = "Cu214200@@$";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    echo json_encode(['success' => false, 'message' => 'Missing email']);
    exit;
}

$userid = rawurlencode($email);
$endpoint = rtrim($NEXTCLOUD_BASE_URL, '/') . "/ocs/v2.php/cloud/users/$userid";

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $ADMIN_USER . ':' . $ADMIN_PASS,
    CURLOPT_HTTPHEADER => ['OCS-APIRequest: true', 'Accept: application/json'],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    echo json_encode(['success' => false, 'message' => 'Could not fetch user info']);
    exit;
}

$data = json_decode($response, true);
if (!$data || !isset($data['ocs']['data'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid response']);
    exit;
}

$u = $data['ocs']['data'];

$quota = $u['quota'] ?? ($u['default_quota'] ?? null);
$usage = $u['used'] ?? ($u['usage'] ?? null);

function toBytes($v) {
    if ($v === null) return null;
    if (is_numeric($v)) return (float)$v;
    if (preg_match('/^([\d\.]+)\s*(TB|T|GB|G|MB|M|KB|K)?/i', trim($v), $m)) {
        $n = (float)$m[1];
        $unit = strtoupper($m[2] ?? '');
        switch ($unit) {
            case 'TB': case 'T': return $n * 1024 * 1024 * 1024 * 1024;
            case 'GB': case 'G': return $n * 1024 * 1024 * 1024;
            case 'MB': case 'M': return $n * 1024 * 1024;
            case 'KB': case 'K': return $n * 1024;
            default: return $n;
        }
    }
    return null;
}

$quota_bytes = toBytes($quota);
$used_bytes = toBytes($usage);

function hr($bytes) {
    if ($bytes === null) return '0 B';
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}

$plan_label = 'Free plan';
if ($quota_bytes !== null) {
    if ($quota_bytes >= 1000 * 1024 * 1024 * 1024) $plan_label = 'Starter plan — 1 TB total';
    else if ($quota_bytes >= 2 * 1024 * 1024 * 1024 * 1024) $plan_label = 'Pro plan — 2 TB total';
    else if ($quota_bytes >= 5 * 1024 * 1024 * 1024 * 1024) $plan_label = 'Enterprise plan — 5 TB total';
}

$files_url = 'https://cloud.monkybite.com/apps/files/';

echo json_encode([
    'success' => true,
    'email' => $email,
    'plan_label' => $plan_label,
    'quota_bytes' => $quota_bytes,
    'used_bytes' => $used_bytes,
    'used_readable' => hr($used_bytes),
    'quota_readable' => hr($quota_bytes),
    'files_url' => $files_url
]);
?>
