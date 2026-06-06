<?php

declare(strict_types=1);

return [
    'enabled'          => (bool) env('ANALYTICS_ENABLED', true),
    'cooldown_seconds' => (int) env('ANALYTICS_COOLDOWN', 300),
];
