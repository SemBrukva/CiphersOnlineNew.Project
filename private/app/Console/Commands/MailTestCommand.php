<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Mail\MailerInterface;
use App\Mail\Message;
use Throwable;

/**
 * Консольная команда для отправки тестового письма.
 */
final readonly class MailTestCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
     * Выполняет отправку тестового письма на указанный email.
     *
     * @param string[] $args
     */
    public function handle(array $args): int
    {
        $email = trim((string) ($args[0] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo 'Usage: php bin/console mail:test you@example.com' . PHP_EOL;

            return 1;
        }

        try {
            $this->mailer->send(
                Message::make($email)
                    ->subject('Test email from Skeleton')
                    ->view('emails/test.tpl')
                    ->with([
                        'email' => $email,
                        'sent_at' => date('Y-m-d H:i:s'),
                        'app_name' => (string) config('app.name', 'Skeleton'),
                    ])
            );

            echo 'Mail sent (or stubbed) to: ' . $email . PHP_EOL;

            return 0;
        } catch (Throwable $e) {
            echo 'Mail error: ' . $e->getMessage() . PHP_EOL;

            return 1;
        }
    }
}
