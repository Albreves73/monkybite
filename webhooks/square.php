<?php
// ------------------------------------------------------------
//  WEBHOOK PROFISSIONAL – SQUARE + NEXTCLOUD (OPÇÃO B)
// ------------------------------------------------------------

require __DIR__ . '/../vendor/autoload.php';

use Square\SquareClient;
use Square\Exceptions\ApiException;

// ------------------------------------------------------------
// CONFIGURAÇÕES
// ------------------------------------------------------------

// Square Webhook Signature Key (Production)
$signatureKey = "rU-xnQos0hKvb_IQx9BFyg";

// URL configurada no painel da Square
$webhookUrl = "https://monkybite.com/webhooks/square.php";

// Nextcloud Admin
$ncAdmin = "admin";
$ncAdminPass = "Cu214200@@$";

// Log
$logFile = "/var/www/monkybite/webhook.log";
function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n", FILE_APPEND);
}

// ------------------------------------------------------------
// 1. Ler payload e assinatura
// ------------------------------------------------------------
$payload = file_get_contents("php://input");
$signature = $_SERVER["HTTP_X_SQUARE_SIGNATURE"] ?? "";

logMsg("Payload recebido: " . $payload);

// ------------------------------------------------------------
// 2. Validar assinatura (Square HMAC-SHA256)
// ------------------------------------------------------------
$computed = base64_encode(hash_hmac("sha256", $webhookUrl . $payload, $signatureKey, true));

if (!hash_equals($computed, $signature)) {
    logMsg("Assinatura inválida");
    http_response_code(400);
    exit("Invalid signature");
}

logMsg("Assinatura válida");

// ------------------------------------------------------------
// 3. Decodificar JSON
// ------------------------------------------------------------
$data = json_decode($payload, true);
$eventType = $data["type"] ?? "";
$payment = $data["data"]["object"]["payment"] ?? null;

if (!$payment) {
    logMsg("Nenhum dado de pagamento");
    http_response_code(200);
    exit("No payment data");
}

$paymentStatus = $payment["status"] ?? "";
$email = strtolower($payment["buyer_email_address"] ?? "");
$note = strtolower($payment["note"] ?? "");

logMsg("Evento: $eventType | Status: $paymentStatus | Email: $email");

// ------------------------------------------------------------
// 4. Confirmar pagamento COMPLETED
// ------------------------------------------------------------
if ($eventType !== "payment.updated" || $paymentStatus !== "COMPLETED") {
    logMsg("Evento ignorado");
    http_response_code(200);
    exit("Ignored");
}

// ------------------------------------------------------------
// 5. Detectar plano
// ------------------------------------------------------------
$plan = str_replace("monkybite subscription - ", "", $note);

$quota = [
    "free" => "5 GB",
    "starter" => "1 TB",
    "pro" => "2 TB",
    "enterprise" => "5 TB"
][$plan] ?? "5 GB";

logMsg("Plano: $plan | Quota: $quota");

// ------------------------------------------------------------
// 6. Criar usuário no Nextcloud
// ------------------------------------------------------------
$ncUser = $email;
$displayName = explode("@", $email)[0];

$createUser = curl_init("https://cloud.monkybite.com/ocs/v1.php/cloud/users");
curl_setopt_array($createUser, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        "userid" => $ncUser,
        "displayName" => $displayName,
        "email" => $email,
        "quota" => $quota
    ]),
    CURLOPT_HTTPHEADER => ["OCS-APIRequest: true"],
    CURLOPT_USERPWD => "$ncAdmin:$ncAdminPass",
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($createUser);
curl_close($createUser);

logMsg("Resposta criação usuário: " . $response);

// ------------------------------------------------------------
// 7. Enviar e-mail de criação de senha
// ------------------------------------------------------------
$sendMail = curl_init("https://cloud.monkybite.com/ocs/v1.php/cloud/users/$ncUser/mail");
curl_setopt_array($sendMail, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["OCS-APIRequest: true"],
    CURLOPT_USERPWD => "$ncAdmin:$ncAdminPass",
    CURLOPT_RETURNTRANSFER => true
]);

$mailResponse = curl_exec($sendMail);
curl_close($sendMail);

logMsg("Resposta envio e-mail: " . $mailResponse);

// ------------------------------------------------------------
// 8. Finalizar
// ------------------------------------------------------------
logMsg("Webhook finalizado com sucesso");
http_response_code(200);
echo "OK";
?>
