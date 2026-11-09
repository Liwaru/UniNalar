<?php

return [
    'enabled' => filter_var(env('UNINALAR_DEMO_PROVISIONING_ENABLED', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
    'mode' => env('UNINALAR_DEMO_PROVISIONING_MODE', ''),
    'database' => env('UNINALAR_DEMO_DATABASE', ''),
];
