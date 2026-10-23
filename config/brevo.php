<?php

return [
    'api_key' => env('BREVO_API_KEY'),
    'from_email' => env('BREVO_FROM_EMAIL', 'suportesquadmaker@gmail.com'),
    'from_name' => env('BREVO_FROM_NAME', 'Squad Maker'),
    'support_email' => env('BREVO_SUPPORT_EMAIL', 'suportesquadmaker@gmail.com'),
];
