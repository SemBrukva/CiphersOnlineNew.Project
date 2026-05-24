<?php

declare(strict_types=1);

/**
 * Удаляет устаревшие файлы сессий из storage/sessions.
 *
 * Запускается раз в час. Порог устаревания берётся из конфига
 * session.lifetime (минуты); если не задан — 1440 (24 ч).
 */

require_once __DIR__ . '/init.php';

use App\Log\Logger;

/** @var Logger $logger */
$logger = app(Logger::class);

// Для Memcached и Redis очистка не нужна — хранилище само удаляет по TTL.
$sessionDriver = config('session.driver', 'file');
if ($sessionDriver !== 'file') {
    echo sprintf(
        '[%s] cleanup_old_sessions: пропущено (driver=%s, очистка не требуется)' . PHP_EOL,
        date('Y-m-d H:i:s'),
        $sessionDriver,
    );
    exit(0);
}

$lifetimeMinutes = (int) config('session.lifetime', 1440);
$threshold       = time() - $lifetimeMinutes * 60;
$sessionDir      = STORAGE_PATH . '/sessions';

if (!is_dir($sessionDir)) {
    exit(0);
}

$deleted = 0;
$errors  = 0;

foreach (new DirectoryIterator($sessionDir) as $file) {
    if ($file->isDot() || !$file->isFile()) {
        continue;
    }

    if ($file->getMTime() >= $threshold) {
        continue;
    }

    if (unlink($file->getRealPath())) {
        $deleted++;
    } else {
        $errors++;
        $logger->error(sprintf(
            '[cleanup_old_sessions] Не удалось удалить файл сессии: %s',
            $file->getRealPath(),
        ));
    }
}

echo sprintf(
    '[%s] cleanup_old_sessions: удалено %d, ошибок %d (порог: %d мин)' . PHP_EOL,
    date('Y-m-d H:i:s'),
    $deleted,
    $errors,
    $lifetimeMinutes,
);
