<?php

declare(strict_types=1);

namespace App\Queue\Jobs;

use App\Container\Container;
use App\Mail\MailerInterface;
use App\Mail\Message;
use App\Queue\ContainerAwareJobInterface;

/**
 * Задача очереди для асинхронной отправки email-сообщения.
 *
 * Сериализует только данные Message (без зависимостей);
 * Mailer резолвится из контейнера в момент выполнения.
 */
final class SendMailJob implements ContainerAwareJobInterface
{
    private ?Container $container = null;

    private string $to;

    private string $subject;

    private ?string $view;

    private ?string $text;

    /** @var array<string, mixed> */
    private array $data;

    /**
     * Создаёт задачу на основе DTO Message.
     */
    public function __construct(Message $message)
    {
        $this->to = (string) $message->getTo();
        $this->subject = (string) $message->getSubject();
        $this->view = $message->getView();
        $this->text = $message->getText();
        $this->data = $message->getData();
    }

    /**
     * Сохраняет ссылку на контейнер для последующего разрешения зависимостей.
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Отправляет письмо синхронно через Mailer.
     */
    public function handle(): void
    {
        if ($this->container === null) {
            throw new \RuntimeException('SendMailJob requires container; run it via the queue worker.');
        }

        $mailer = $this->container->get(MailerInterface::class);

        $message = Message::make($this->to)->subject($this->subject);

        if ($this->view !== null && $this->view !== '') {
            $message->view($this->view)->with($this->data);
        } elseif ($this->text !== null && $this->text !== '') {
            $message->text($this->text);
        }

        $mailer->send($message);
    }

    /**
     * Возвращает поля, подлежащие сериализации (без контейнера).
     *
     * @return string[]
     */
    public function __sleep(): array
    {
        return ['to', 'subject', 'view', 'text', 'data'];
    }
}
