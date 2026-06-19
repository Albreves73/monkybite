<?php
declare(strict_types=1);

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

function nextcloudRequest(string $method, string $url, string $user, string $pass, ?array $postFields = null): array {
    $ch = curl_init($url);
    $headers = [
        'OCS-APIRequest: true',
        'Accept: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $user . ':' . $pass,
        CURLOPT_HTTPHEADER => $headers,
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

try {
    $ncBaseUrl = 'https://cloud.monkybite.com';
    $ncAdminUser = 'admin';
    $ncAdminPass = 'Cu214200@@$';

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $plan = trim($_POST['plan'] ?? 'free');

    if ($username === '' || $email === '' || $password === '') {
        throw new Exception('Preencha usuário, email e senha.');
    }

    $group = getPlanGroup($plan);

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
        throw new Exception('Erro cURL ao criar usuário: ' . $createRes['curl_error']);
    }

    $body = $createRes['response'] ?? '';
    if ($createRes['http_code'] < 200 || $createRes['http_code'] >= 300) {
        throw new Exception('Falha ao criar usuário no Nextcloud: ' . $body);
    }

    echo 'Usuário criado com sucesso no grupo ' . $group;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Sign up failed: ' . $e->getMessage();
}
