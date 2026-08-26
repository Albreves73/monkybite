<?php

$config = include "config.php";

$plan = $_POST["plan"];
$email = $_POST["email"];

// Define price based on plan
$prices = [
    "free" => 0,
    "starter" => 499,
    "pro" => 999,
    "enterprise" => 1999
];

$amount = $prices[$plan];

// Square API payload
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
    ],
    "checkout_options" => [
        "redirect_url" => "https://monkybite.com/payment-success.php"
    ]
];

$ch = curl_init("https://connect.squareup.com/v2/checkout");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $config["square_access_token"]
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

// Return checkout URL
echo $response["checkout"]["checkout_page_url"];
?>
