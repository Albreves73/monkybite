<?php

// =======================================
//  CONFIGURAÇÃO DO SQUARE (PRODUÇÃO)
// =======================================

// 🔐 Token de produção da Square
// Substitua depois pelo token real
$SQUARE_ACCESS_TOKEN = "EAAAl4t1IxaAmz6YxxT8UAcNpIU2Y_fhmj-eooVFxQtEgY2OtsesFCU40X8Rvtc3";

// 🏬 Location ID de produção
$SQUARE_LOCATION_ID = "sq0idp-JHruqkfGcQdQfmgDQYjnUQ";

// 🔑 Webhook Signature Key (Production)
$SQUARE_WEBHOOK_SIGNATURE = "rU-xnQos0hKvb_IQx9BFyg";

// 🌐 URL do webhook configurado no Square Dashboard
$SQUARE_WEBHOOK_URL = "https://monkybite.com/webhooks/square.php";

// 💵 Preços dos planos (em centavos)
$PLAN_PRICES = [
    "free"       => 0,
    "starter"    => 499,
    "pro"        => 999,
    "enterprise" => 1999
];

?>
