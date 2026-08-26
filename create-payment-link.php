<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = include "config.php";

$plan = $_POST["plan"];
$email = $_POST["email"];

$prices = [
    "free" => 0,
    "starter" => 499,
    "pro" => 999,
    "enterprise" => 1999
];

$amount = $prices[$plan];

$payload = [
    "idempotency_key" => uniqid(),
    "order" => [
        "location_id" => $config["square_location_id"],
        "line_items" => [
            [
                "name" => strtoupper($plan) . " PLAN",
                "quantity" => "1",
                "base_price_money" => [
                    "amount" => $amount,
                    "currency" => "USD"
                ]
            ]
        ]
    ]
];

$ch = curl_init("https://connect.squareup.com/v2/online-checkout/payment-links");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $config["square_access_token"]
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo $response["payment_link"]["url"];
?>
