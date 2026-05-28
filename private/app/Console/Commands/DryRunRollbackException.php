<?php

declare(strict_types=1);

namespace App\Console\Commands;

use RuntimeException;

/**
 * Служебное исключение для отката транзакции в режиме dry-run.
 */
final class DryRunRollbackException extends RuntimeException
{
}

