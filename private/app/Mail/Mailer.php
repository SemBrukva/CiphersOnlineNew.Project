<?php

declare(strict_types=1);

namespace App\Mail;

use App\Container\Container;
use App\Log\LoggerInterface;
use App\Queue\Jobs\SendMailJob;
use App\Queue\QueueManager;
use App\View\View;
use RuntimeException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Сервис отправки писем через Symfony Mailer.
 */
final class Mailer implements MailerInterface
{
    /** @var array<string, mixed> */
    private array $config;

    private View $view;

    private LoggerInterface $logger;

    private MailjetStubSender $mailjetStub;

    private Container $container;

    private ?TransportInterface $transport = null;

    /**
     * Создаёт экземпляр сервиса отправки писем.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(
        array $config,
        View $view,
        LoggerInterface $logger,
        MailjetStubSender $mailjetStub,
        Container $container
    ) {
        $this->config = $config;
        $this->view = $view;
        $this->logger = $logger;
        $this->mailjetStub = $mailjetStub;
        $this->container = $container;
    }

    /**
     * Создаёт SendMailJob и помещает его в очередь для асинхронной отправки.
     */
    public function queue(Message $message, int $delay = 0): int
    {
        $queue = $this->container->get(QueueManager::class);

        return $queue->push(new SendMailJob($message), $delay);
    }

    /**
     * Отправляет сообщение через выбранный транспорт.
     */
    public function send(Message $message): void
    {
        $to = $message->getTo();
        $subject = $message->getSubject();

        if ($to === null || $to === '') {
            throw new RuntimeException('Mail recipient is required.');
        }

        if ($subject === null || $subject === '') {
            throw new RuntimeException('Mail subject is required.');
        }

        $body = $this->resolveBody($message);
        $dsn = (string) ($this->config['dsn'] ?? 'null://null');
        $fromAddress = (string) ($this->config['from']['address'] ?? 'no-reply@example.com');
        $fromName = (string) ($this->config['from']['name'] ?? 'Application');

        if ($this->isMailjetStubDsn($dsn)) {
            $this->mailjetStub->send([
                'from' => ['email' => $fromAddress, 'name' => $fromName],
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
            ]);

            $this->logger->info('Mailjet stub message stored for {to}', ['to' => $to, 'subject' => $subject]);

            return;
        }

        $email = (new Email())
            ->from(new Address($fromAddress, $fromName))
            ->to(new Address($to))
            ->subject($subject)
            ->html($body)
            ->text(strip_tags($body));

        $envelope = new Envelope(new Address($fromAddress), [new Address($to)]);
        $mailer = new SymfonyMailer($this->getTransport($dsn));
        $mailer->send($email, $envelope);
    }

    /**
     * Рендерит тело письма из шаблона или plain-text поля.
     */
    private function resolveBody(Message $message): string
    {
        $view = $message->getView();

        if ($view !== null && $view !== '') {
            return $this->view->fetch($view, $message->getData());
        }

        $text = $message->getText();

        if ($text !== null && $text !== '') {
            return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        }

        throw new RuntimeException('Mail body is required. Set view() or text().');
    }

    /**
     * Возвращает singleton-транспорт по DSN.
     */
    private function getTransport(string $dsn): TransportInterface
    {
        if ($this->transport !== null) {
            return $this->transport;
        }

        $this->transport = Transport::fromDsn($dsn);

        return $this->transport;
    }

    /**
     * Проверяет, что DSN указывает на заглушку Mailjet.
     */
    private function isMailjetStubDsn(string $dsn): bool
    {
        return str_starts_with($dsn, 'mailjet+stub://');
    }
}
