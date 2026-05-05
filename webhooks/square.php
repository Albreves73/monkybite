<?php
require __DIR__ . '/../square-config.php';

// =======================================
//  LOG PARA DEBUG
// =======================================
$logFile = "/var/www/monkybite/webhook.log";

function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n", FILE_APPEND);
}

// =======================================
//  RECEBE PAYLOAD E ASSINATURA
// =======================================
$payload = file_get_contents("php://input");
$signature = $_SERVER["HTTP_X_SQUARE_SIGNATURE"] ?? "";

logMsg("Payload recebido: " . $payload);

// =======================================
//  VALIDA ASSINATURA DO WEBHOOK
// =======================================
$computed = base64_encode(
    hash_hmac(
        "sha256",
        $SQUARE_WEBHOOK_URL . $payload,
        $SQUARE_WEBHOOK_SIGNATURE,
        true
    )
);

if (!hash_equals($computed, $signature)) {
    logMsg("Assinatura inválida");
    http_response_code(400);
    exit("Invalid signature");
}

logMsg("Assinatura válida");

// =======================================
//  DECODIFICA PAYLOAD
// =======================================
$data = json_decode($payload, true);
$payment = $data["data"]["object"]["payment"] ?? null;

if (!$payment) {
    logMsg("Nenhum dado de pagamento encontrado");
    exit("No payment data");
}

$status = $payment["status"] ?? "";
$userId = intval($payment["reference_id"] ?? 0);

logMsg("Status: $status | UserID: $userId");

// =======================================
//  IGNORA SE NÃO FOR COMPLETED
// =======================================
if ($status !== "COMPLETED") {
    logMsg("Pagamento não completado, ignorado");
    exit("Ignored");
}

// =======================================
//  ATIVA USUÁRIO NO BANCO
// =======================================
$pdo = new PDO("mysql:host=localhost;dbname=monkybite;charset=utf8", "root", "");

$stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
$stmt->execute([$userId]);

logMsg("Usuário ativado no banco");

// =======================================
//  BUSCA EMAIL DO USUÁRIO
// =======================================
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$email = $stmt->fetchColumn();

if (!$email) {
    logMsg("Email não encontrado para userId $userId");
    exit("Email not found");
}

logMsg("Criando usuário no Nextcloud: $email");

// =======================================
//  CRIA USUÁRIO NO NEXTCLOUD VIA OCC
// =======================================

// Senha temporária (será substituída pelo reset enviado por email)
putenv("OC_PASS=newpassword123");

$cmd = "sudo -u www-data php /var/www/nextcloud/occ user:add --password-from-env --display-name=\"$email\" \"$email\"";

exec($cmd, $output);
logMsg("OCC output: " . implode("\n", $output));

// =======================================
//  ENVIA EMAIL DE CRIAÇÃO DE SENHA
// =======================================
exec("sudo -u www-data php /var/www/nextcloud/occ user:resetpassword --send-email \"$email\"");

logMsg("Email de criação de senha enviado");

// =======================================
//  FINALIZA
// =======================================
echo "OK";
?>
