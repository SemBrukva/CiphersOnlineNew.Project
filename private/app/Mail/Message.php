<?php

declare(strict_types=1);

namespace App\Mail;

/**
 * DTO почтового сообщения с fluent API.
 */
final class Message
{
    /**
     * Создаёт экземпляр DTO с начальным получателем.
     */
    public static function make(?string $to = null): self
    {
        $message = new self();

        if ($to !== null) {
            $message->to($to);
        }

        return $message;
    }

    private ?string $to = null;
    private ?string $subject = null;
    private ?string $view = null;
    private ?string $text = null;

    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * Устанавливает email получателя.
     */
    public function to(string $email): self
    {
        $this->to = trim($email);

        return $this;
    }

    /**
     * Устанавливает тему письма.
     */
    public function subject(string $subject): self
    {
        $this->subject = trim($subject);

        return $this;
    }

    /**
     * Устанавливает шаблон письма.
     */
    public function view(string $template): self
    {
        $this->view = $template;

        return $this;
    }

    /**
     * Устанавливает plain-text тело письма.
     */
    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Передаёт данные в шаблон письма.
     *
     * @param array<string, mixed> $data
     */
    public function with(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Возвращает email получателя.
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * Возвращает тему письма.
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Возвращает имя шаблона.
     */
    public function getView(): ?string
    {
        return $this->view;
    }

    /**
     * Возвращает plain-text тело письма.
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Возвращает данные шаблона.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
