<?php

declare(strict_types=1);

use App\Event\Events\UserRegistered;
use App\Event\Listeners\SendVerificationEmail;

// Карта событий и листенеров: Event::class => [Listener::class, ...]

return [
    UserRegistered::class => [
        SendVerificationEmail::class,
    ],
];
