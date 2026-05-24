<?php

declare(strict_types=1);

namespace App\Mail;

/**
 * Контракт отправки почтовых сообщений.
 */
interface MailerInterface
{
    /**
     * Отправляет сообщение.
     */
    public function send(Message $message): void;

    /**
     * Помещает отправку сообщения в очередь для асинхронной обработки.
     *
     * @return int ID созданной задачи в таблице jobs.
     */
    public function queue(Message $message, int $delay = 0): int;
}
