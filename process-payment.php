<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function pickField(array $source, array $names): string {
    foreach ($names as $name) {
        if (isset($source[$name])) {
            $value = trim((string)$source[$name]);
            if ($value !== '') {
                return $value;
            }
        }
    }
    return '';
}

function planToGroup(string $plan): string {
    $map = [
        'free' => 'Free',
        'starter' => 'Starter',
        'pro' => 'Pro',
        'enterprise' => 'Enterprise',
    ];
    $key = strtolower(trim($plan));
    if (!isset($map[$key])) {
        throw new Exception('Invalid plan.');
    }
    return $map[$key];
}

function planToQuota(string $plan): string {
    $map = [
        'free' => '2 GB',
        'starter' => '10 GB',
        'pro' => '50 GB',
        'enterprise' => '100 GB',
    ];
    $key = strtolower(trim($plan));
    if (!isset($map[$key])) {
        throw new Exception('Invalid plan.');
    }
    return $map[$key];
}

function squareCreatePayment(string $accessToken, string $locationId, string $sourceId, int $amountCents, string $currency = 'USD'): array {
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
        throw new Exception('Invalid request method.');
    }

    $ncBaseUrl = 'https://cloud.monkybite.com';
    $ncAdminUser = 'admin';
    $ncAdminPass = 'Cu214200@@$';

    $squareAccessToken = 'EAAAlz_CU24QwkuDeXtJQQ6zg1qRviQZ2ESc7kLDmm1hHP3hPCOrC9qEp2TL4pYw';
    $squareLocationId = 'LTZ1WY5B11Q9Q';

    $username = pickField($_POST, ['username', 'user', 'login']);
    $email = pickField($_POST, ['email', 'mail']);
    $password = pickField($_POST, ['password', 'pass']);
    $plan = pickField($_POST, ['plan', 'tier', 'package']);
    if ($plan === '') {
        $plan = 'free';
    }

    $sourceId = pickField($_POST, ['source_id', 'nonce', 'token', 'payment_token']);
    $amount = pickField($_POST, ['amount', 'price', 'total']);
    $currency = pickField($_POST, ['currency']);
    if ($currency === '') {
        $currency = 'USD';
    }

    if ($username === '' || $email === '' || $password === '') {
        throw new Exception('Please fill in username, email, and password.');
    }

    if ($sourceId === '') {
        throw new Exception('Payment source_id is required.');
    }

    if ($amount === '') {
        throw new Exception('Payment amount is required.');
    }

    $amountCents = (int) round(((float)$amount) * 100);
    if ($amountCents <= 0) {
        throw new Exception('Invalid payment amount.');
    }

    $squareRes = squareCreatePayment($squareAccessToken, $squareLocationId, $sourceId, $amountCents, strtoupper($currency));
    if ($squareRes['curl_error']) {
        throw new Exception('Square cURL error: ' . $squareRes['curl_error']);
    }
    if ($squareRes['http_code'] < 200 || $squareRes['http_code'] >= 300) {
        throw new Exception('Square payment failed: ' . ($squareRes['response'] ?? ''));
    }

    $squareBody = json_decode((string)$squareRes['response'], true);
    if (!is_array($squareBody)) {
        throw new Exception('Invalid Square response.');
    }
    if (($squareBody['payment']['status'] ?? '') !== 'COMPLETED') {
        throw new Exception('Payment was not completed: ' . ($squareRes['response'] ?? ''));
    }

    $group = planToGroup($plan);
    $quota = planToQuota($plan);

    $createUserUrl = $ncBaseUrl . '/ocs/v2.php/cloud/users';
    $createRes = nextcloudRequest('POST', $createUserUrl, $ncAdminUser, $ncAdminPass, [
        'userid' => $username,
        'password' => $password,
        'displayName' => $username,
        'email' => $email,
        'groups[]' => $group,
        'quota' => $quota,
    ]);

    if ($createRes['curl_error']) {
        throw new Exception('Nextcloud cURL error: ' . $createRes['curl_error']);
    }
    if ($createRes['http_code'] < 200 || $createRes['http_code'] >= 300) {
        throw new Exception('Failed to create Nextcloud user: ' . ($createRes['response'] ?? ''));
    }

    echo 'Payment approved. Account created successfully.';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Sign up failed: ' . $e->getMessage();
}
