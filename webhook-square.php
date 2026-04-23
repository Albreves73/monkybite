<?php
// -----------------------------------------
// 1. Validate Square Webhook Signature
// -----------------------------------------
$signatureKey = "YOUR_WEBHOOK_SIGNATURE_KEY";

$payload = file_get_contents("php://input");
$signature = $_SERVER["HTTP_X_SQUARE_SIGNATURE"] ?? "";

$valid = hash_hmac("sha1", $payload, $signatureKey);

if (!hash_equals($valid, $signature)) {
    http_response_code(400);
    echo "Invalid signature";
    exit;
}

// -----------------------------------------
// 2. Decode webhook JSON
// -----------------------------------------
$data = json_decode($payload, true);

$eventType = $data["type"] ?? "";
$payment = $data["data"]["object"]["payment"] ?? null;

if (!$payment) {
    http_response_code(200);
    echo "No payment data";
    exit;
}

$paymentStatus = $payment["status"] ?? "";
$email = strtolower($payment["buyer_email_address"] ?? "");
$note = strtolower($payment["note"] ?? "");

// Extract plan from note
$plan = str_replace("monkybite subscription - ", "", $note);

// Only process successful payments
if ($eventType !== "payment.updated" || $paymentStatus !== "COMPLETED") {
    http_response_code(200);
    echo "Ignored";
    exit;
}

// -----------------------------------------
// 3. Prepare Nextcloud user data
// -----------------------------------------
$ncUser = $email; // username = email
$displayName = explode("@", $email)[0];

$ncAdmin = "NEXTCLOUD_ADMIN_USER";
$ncAdminPass = "NEXTCLOUD_ADMIN_PASSWORD";

$quota = [
    "free" => "5 GB",
    "starter" => "1 TB",
    "pro" => "2 TB",
    "enterprise" => "5 TB"
][$plan] ?? "5 GB";

// -----------------------------------------
// 4. Create user in Nextcloud (WITHOUT PASSWORD)
// -----------------------------------------
$createUser = curl_init("https://cloud.monkybite.com/ocs/v1.php/cloud/users");
curl_setopt_array($createUser, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        "userid" => $ncUser,
        "displayName" => $displayName,
        "quota" => $quota
    ]),
    CURLOPT_HTTPHEADER => ["OCS-APIRequest: true"],
    CURLOPT_USERPWD => "$ncAdmin:$ncAdminPass",
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($createUser);
curl_close($createUser);

// -----------------------------------------
// 5. Send "Set Password" email
// -----------------------------------------
$sendMail = curl_init("https://cloud.monkybite.com/ocs/v1.php/cloud/users/$ncUser/mail");
curl_setopt_array($sendMail, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["OCS-APIRequest: true"],
    CURLOPT_USERPWD => "$ncAdmin:$ncAdminPass",
    CURLOPT_RETURNTRANSFER => true
]);

curl_exec($sendMail);
curl_close($sendMail);

// -----------------------------------------
// 6. Respond to Square
// -----------------------------------------
http_response_code(200);
echo "OK";
?>
