<?php

return [

    // ============================================
    // NEXTCLOUD CONFIGURATION
    // ============================================

    "nextcloud_host" => "https://cloud.monkybite.com",

    "admin_user" => "admin",
    "admin_pass" => "Cu214200@@$",

    // ============================================
    // SQUARE CONFIGURATION
    // ============================================

    // 🔥 OBRIGATÓRIO para Apple Pay, Google Pay e Cartão
    "square_application_id" => "sq0idp-JHruqkfGcQdQfmgDQYjnUQ",

    // 🔥 Token de produção (já está certo)
    "square_access_token" => "EAAAlz_CU24QwkuDeXtJQQ6zg1qRviQZ2ESc7kLDmm1hHP3hPCOrC9qEp2TL4pYw",

    // 🔥 Location ID de produção (já está certo)
    "square_location_id" => "LTZ1WY5B11Q9Q",

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
