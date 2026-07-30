<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $squareAccessToken = 'EAAAlz_CU24QwkuDeXtJQQ6zg1qRviQZ2ESc7kLDmm1hHP3hPCOrC9qEp2TL4pYw';
    $squareLocationId = 'LTZ1WY5B11Q9Q';

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sourceId = trim($input['token'] ?? '');
    $plan = trim($input['plan'] ?? 'free');

    // aqui você pode mapear valor por plano
    $PLAN_PRICES = [
        'free' => 0,
        'starter' => 499,
        'pro' => 999,
        'enterprise' => 1999,
    ];

    $amountCents = $PLAN_PRICES[$plan] ?? 0;
    if ($amountCents <= 0 && $plan !== 'free') {
        throw new Exception('Invalid plan price.');
    }

    if ($sourceId === '') {
        throw new Exception('Payment source_id is required.');
    }

    if ($plan === 'free') {
        echo json_encode(['success' => true, 'free' => true]);
        exit;
    }

    $squareRes = squareCreatePayment($squareAccessToken, $squareLocationId, $sourceId, $amountCents, 'USD');
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
        throw new Exception('Payment was not completed.');
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
