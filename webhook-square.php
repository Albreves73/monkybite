<?php
// -----------------------------------------
// 1. Validate Square Webhook Signature
// -----------------------------------------
$signatureKey = "YOUR_WEBHOOK_SIGNATURE_KEY"; // from Square Dashboard

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
$paymentStatus = $data["data"]["object"]["payment"]["status"] ?? "";
$email = $data["data"]["object"]["payment"]["buyer_email_address"] ?? "";
$note = strtolower($data["data"]["object"]["payment"]["note"] ?? "");

// Extract plan from note
$plan = str_replace("monkybite subscription - ", "", $note);

// Only process successful payments
if ($eventType !== "payment.updated" || $paymentStatus !== "COMPLETED") {
    http_response_code(200);
    echo "Ignored";
    exit;
}

// -----------------------------------------
// 3. Connect to MySQL
// -----------------------------------------
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "monkybite";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo "Database error";
    exit;
}

// -----------------------------------------
// 4. Get pending user
// -----------------------------------------
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'pending'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo "User not found";
    exit;
}

$userData = $result->fetch_assoc();
$stmt->close();

// -----------------------------------------
// 5. Create user in Nextcloud
// -----------------------------------------
$ncUser = $userData["email"];
$ncPass = $userData["password"]; // If hashed, generate a new password
$first = $userData["firstName"];
$last = $userData["lastName"];

$ncAdmin = "NEXTCLOUD_ADMIN_USER";
$ncAdminPass = "NEXTCLOUD_ADMIN_PASSWORD";

$quota = [
    "free" => "5 GB",
    "starter" => "1 TB",
    "pro" => "2 TB",
    "enterprise" => "5 TB"
][$plan] ?? "5 GB";

// Create user
$createUser = curl_init("https://cloud.monkybite.com/ocs/v1.php/cloud/users");
curl_setopt_array($createUser, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        "userid" => $ncUser,
        "password" => $ncPass,
        "displayName" => "$first $last",
        "quota" => $quota
    ]),
    CURLOPT_HTTPHEADER => ["OCS-APIRequest: true"],
    CURLOPT_USERPWD => "$ncAdmin:$ncAdminPass",
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($createUser);
curl_close($createUser);

// -----------------------------------------
// 6. Update user status in database
// -----------------------------------------
$update = $conn->prepare("UPDATE users SET status = 'active' WHERE email = ?");
$update->bind_param("s", $email);
$update->execute();
$update->close();

$conn->close();

// -----------------------------------------
// 7. Respond to Square
// -----------------------------------------
http_response_code(200);
echo "OK";
?>
