<?php

declare(strict_types=1);

namespace App\Log;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Контракт логгера приложения на основе PSR-3.
 */
interface LoggerInterface extends PsrLoggerInterface
{
}
