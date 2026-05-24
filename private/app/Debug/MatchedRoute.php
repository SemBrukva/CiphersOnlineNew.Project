<?php

declare(strict_types=1);

namespace App\Debug;

/**
 * Хранит данные о маршруте, совпавшем с текущим запросом.
 *
 * Заполняется роутером при диспетчеризации; читается DebugInfo при сборке отладочных данных.
 * Класс намеренно мутабельный — синглтон-хранитель состояния одного запроса.
 */
final class MatchedRoute
{
    /** @var string|null FQCN контроллера. */
    private ?string $controller = null;

    /** @var string|null Имя метода контроллера. */
    private ?string $action = null;

    /** @var string[] Список FQCN middleware, применённых к маршруту. */
    private array $middleware = [];

    /** @var string|null Ключ маршрута вида «METHOD /pattern». */
    private ?string $pattern = null;

    /**
     * Заполняет данные после успешного сопоставления маршрута.
     *
     * @param string   $controller FQCN класса контроллера.
     * @param string   $action     Имя вызываемого метода.
     * @param string[] $middleware Список FQCN middleware маршрута.
     * @param string   $pattern    Ключ маршрута (например, «GET /users/{id}»).
     */
    public function fill(string $controller, string $action, array $middleware, string $pattern): void
    {
        $this->controller = $controller;
        $this->action     = $action;
        $this->middleware = $middleware;
        $this->pattern    = $pattern;
    }

    /** Возвращает краткое имя класса контроллера или null. */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /** Возвращает имя метода контроллера или null. */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Возвращает список FQCN middleware маршрута.
     *
     * @return string[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /** Возвращает ключ маршрута или null, если маршрут не был сопоставлен. */
    public function getPattern(): ?string
    {
        return $this->pattern;
    }
}
