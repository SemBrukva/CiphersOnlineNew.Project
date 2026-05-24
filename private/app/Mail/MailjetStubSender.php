<?php

declare(strict_types=1);

namespace App\Mail;

/**
 * Заглушка отправщика Mailjet для локальной разработки.
 *
 * Ничего не отправляет во внешний сервис, а сохраняет payload в лог-файл.
 */
final readonly class MailjetStubSender
{
    /**
     * Создаёт заглушку отправщика.
     */
    public function __construct(private string $logPath)
    {
    }

    /**
     * Пишет эмулированный payload отправки в файл.
     *
     * @param array<string, mixed> $payload
     */
    public function send(array $payload): void
    {
        $dir = dirname($this->logPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = json_encode([
            'ts' => date('c'),
            'provider' => 'mailjet-stub',
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        file_put_contents($this->logPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
