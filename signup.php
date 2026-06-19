<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

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

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $plan = trim($_POST['plan'] ?? 'free');

    if ($username === '' || $email === '' || $password === '') {
        throw new Exception('Please fill in username, email, and password.');
    }

    $group = planToGroup($plan);
    $quota = planToQuota($plan);

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
            'quota' => $quota,
        ]
    );

    if ($createRes['curl_error']) {
        throw new Exception('Nextcloud cURL error: ' . $createRes['curl_error']);
    }

    if ($createRes['http_code'] < 200 || $createRes['http_code'] >= 300) {
        throw new Exception('Failed to create Nextcloud user: ' . ($createRes['response'] ?? ''));
    }

    echo 'Signup completed successfully.';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Sign up failed: ' . $e->getMessage();
}
