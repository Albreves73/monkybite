<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function getPlanGroup(string $plan): string {
    $map = [
        'free' => 'Free',
        'starter' => 'Starter',
        'pro' => 'Pro',
        'enterprise' => 'Enterprise',
    ];

    $key = strtolower(trim($plan));
    if (!isset($map[$key])) {
        throw new Exception('Plano inválido.');
    }

    return $map[$key];
}

function getPlanQuota(string $plan): string {
    $map = [
        'free' => '2 GB',
        'starter' => '10 GB',
        'pro' => '50 GB',
        'enterprise' => '100 GB',
    ];

    $key = strtolower(trim($plan));
    if (!isset($map[$key])) {
        throw new Exception('Plano inválido.');
    }

    return $map[$key];
}

function squareCreatePayment(
    string $accessToken,
    string $locationId,
    string $sourceId,
    int $amountCents,
    string $currency = 'USD'
): array {
    $url = 'https://connect.squareup.com/v2/payments';

    $payload = [
        'source_id' => $sourceId,
        'idempotency_key' => bin2hex(random_bytes(16)),
        'amount_money' => [
            'amount' => $amountCents,
            'currency' => $currency,
        ],
        'location_id' => $locationId,
        'autocomplete' => true,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlErr,
        'payload' => $payload,
    ];
}

function nextcloudRequest(string $method, string $url, string $user, string $pass, ?array $postFields = null): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $user . ':' . $pass,
        CURLOPT_HTTPHEADER => [
            'OCS-APIRequest: true',
            'Accept: application/json',
        ],
    ]);

    if ($postFields !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlErr,
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    $squareAccessToken = 'EAAAlz_CU24QwkuDeXtJQQ6zg1qRviQZ2ESc7kLDmm1hHP3hPCOrC9qEp2TL4pYw';
    $squareLocationId = 'LTZ1WY5B11Q9Q';

    $ncBaseUrl = 'https://cloud.monkybite.com';
    $ncAdminUser = 'admin';
    $ncAdminPass = 'Cu214200@@$';

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $plan = trim($_POST['plan'] ?? 'free');
    $sourceId = trim($_POST['source_id'] ?? '');
    $amount = trim($_POST['amount'] ?? '0');
    $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));

    if ($username === '' || $email === '' || $password === '') {
        throw new Exception('Usuário, email e senha são obrigatórios.');
    }

    $group = getPlanGroup($plan);
    $quota = getPlanQuota($plan);

    if ($sourceId === '') {
        throw new Exception('source_id não informado.');
    }

    $amountCents = (int) round(((float) $amount) * 100);
    if ($amountCents <= 0) {
        throw new Exception('Valor do pagamento inválido.');
    }

    $squareRes = squareCreatePayment(
        $squareAccessToken,
        $squareLocationId,
        $sourceId,
        $amountCents,
        $currency
    );

    if ($squareRes['curl_error']) {
        throw new Exception('Erro cURL na Square: ' . $squareRes['curl_error']);
    }

    if ($squareRes['http_code'] < 200 || $squareRes['http_code'] >= 300) {
        throw new Exception('Falha no pagamento Square: ' . ($squareRes['response'] ?? ''));
    }

    $squareBody = json_decode((string)$squareRes['response'], true);
    if (!is_array($squareBody)) {
        throw new Exception('Resposta inválida da Square.');
    }

    if (!isset($squareBody['payment']['status']) || $squareBody['payment']['status'] !== 'COMPLETED') {
        throw new Exception('Pagamento não foi concluído: ' . ($squareRes['response'] ?? ''));
    }

    $createUserUrl = $ncBaseUrl . '/ocs/v2.php/cloud/users';
    $createRes = nextcloudRequest(
        'POST',
        $createUserUrl,
        $ncAdminUser,
        $ncAdminPass,
        [
            'userid' => $username,
            'password' => $password,
            'displayName' => $username,
            'email' => $email,
            'groups[]' => $group,
        ]
    );

    if ($createRes['curl_error']) {
        throw new Exception('Erro cURL ao criar usuário no Nextcloud: ' . $createRes['curl_error']);
    }

    if ($createRes['http_code'] < 200 || $createRes['http_code'] >= 300) {
        throw new Exception('Falha ao criar usuário no Nextcloud: ' . ($createRes['response'] ?? ''));
    }

    $setQuotaUrl = $ncBaseUrl . '/ocs/v2.php/cloud/users/' . rawurlencode($username);
    $quotaRes = nextcloudRequest(
        'PUT',
        $setQuotaUrl,
        $ncAdminUser,
        $ncAdminPass,
        ['key' => 'quota', 'value' => $quota]
    );

    if ($quotaRes['curl_error']) {
        throw new Exception('Erro cURL ao atualizar quota no Nextcloud: ' . $quotaRes['curl_error']);
    }

    if ($quotaRes['http_code'] < 200 || $quotaRes['http_code'] >= 300) {
        throw new Exception('Falha ao atualizar quota no Nextcloud: ' . ($quotaRes['response'] ?? ''));
    }

    echo 'Pagamento aprovado, usuário criado e quota atualizada com sucesso.';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Payment failed: ' . $e->getMessage();
}
