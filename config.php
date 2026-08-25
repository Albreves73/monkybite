<?php

return [

    // ============================================
    // NEXTCLOUD CONFIGURATION
    // ============================================

    "nextcloud_host" => "https://cloud.monkybite.com",

    // 🔥 Coloque sua senha REAL aqui depois
    "admin_user" => "admin",
    "admin_pass" => "Cu214200@@$",

    // ============================================
    // PLANOS, QUOTAS E GRUPOS
    // ============================================

    "plans" => [

        "free" => [
            "quota" => "5 GB",
            "group" => "free"
        ],

        "starter" => [
            "quota" => "1 TB",
            "group" => "starter"
        ],

        "pro" => [
            "quota" => "2 TB",
            "group" => "pro"
        ],

        "enterprise" => [
            "quota" => "5 TB",
            "group" => "enterprise"
        ]
    ]
];
