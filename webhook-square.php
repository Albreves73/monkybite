<?php
// -----------------------------------------
// CONFIGURAÇÕES
// -----------------------------------------

// Signature Key do Square (Production)
$signatureKey = "SrU-xnQos0hKvb_IQx9BFyg";

// URL EXATA configurada no Square
$notificationUrl = "https://monkybite.com/webhook-square.php";

// Nextcloud Admin
$ncAdmin = "admin";
$ncAdminPass = "Cu214200@@$";

// Arquivo de log
$logFile = "/var/www/monkybite/webhook.log";

function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n", FILE_APPEND);
}

// -----------------------------------------
// 1. Ler payload e assinatura
// -----------------------------------------
$payload = file_get_contents("php://input");
$signature = $_SERVER["HTTP_X_SQUARE_SIGNATURE"] ?? "";

logMsg("Recebido payload: " . $payload);

// -----------------------------------------
// 2. Validar assinatura (Square HMAC-SHA1)
// -----------------------------------------
$computed = base64_encode(hash_hmac("sha1", $notificationUrl . $payload, $signatureKey, true));

if (!hash_equals($computed, $signature)) {
    logMsg("Assinatura inválida");
    http_response_code(400);
    echo "Invalid signature";
    exit;
}

logMsg("Assinatura válida");

// -----------------------------------------
// 3. Decodificar JSON
// -----------------------------------------
$data = json_decode($payload, true);

$eventType = $data["type"] ?? "";
$payment = $data["data"]["object"]["payment"] ?? null;

if (!$payment) {
    logMsg("Sem dados de pagamento");
    http_response_code(200);
    echo "No payment data";
    exit;
}

$paymentStatus = $payment["status"] ?? "";
$email = strtolower($payment["buyer_email_address"] ?? "");
$note = strtolower($payment["note"] ?? "");

logMsg("Evento: $eventType | Status: $paymentStatus | Email: $email");

// -----------------------------------------
// 4. Verificar se é pagamento COMPLETED
// -----------------------------------------
if ($eventType !== "payment.updated" || $paymentStatus !== "COMPLETED") {
    logMsg("Evento ignorado");
    http_response_code(200);
    echo "Ignored";
    exit;
}

// -----------------------------------------
// 5. Extrair plano
// -----------------------------------------
$plan = str_replace("monkybite subscription - ", "", $note);

