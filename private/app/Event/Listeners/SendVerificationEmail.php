<?php

declare(strict_types=1);

namespace App\Event\Listeners;

use App\Event\Events\UserRegistered;
use App\Log\LoggerInterface;
use App\Mail\MailerInterface;
use App\Mail\Message;
use Throwable;

/**
 * Листенер отправки верификационного письма после регистрации.
 */
final readonly class SendVerificationEmail
{
    /**
     * Создаёт экземпляр листенера.
     */
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Отправляет письмо верификации, если она требуется.
     */
    public function __invoke(UserRegistered $event): void
    {
        if (!$event->verificationRequired) {
            return;
        }

        $email = $event->user['email'] ?? null;
        $name = $event->user['name'] ?? 'user';
        $token = $event->user['email_verification_token'] ?? null;

        if (!is_string($email) || $email === '' || !is_string($token) || $token === '') {
            return;
        }

        $subject = 'Verify your email';
        $link = rtrim((string) config('app.url', ''), '/') . '/verify-email?token=' . rawurlencode($token);
        $text = "Hello {$name}!\nPlease verify your email: {$link}";

        try {
            $this->mailer->send(
                Message::make($email)
                    ->subject($subject)
                    ->text($text)
            );
        } catch (Throwable $e) {
            $this->logger->warning('Failed to send verification email: {error}', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);
        }
    }
}
