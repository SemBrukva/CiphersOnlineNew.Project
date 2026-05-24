<?php

declare(strict_types=1);

namespace App\Debug;

use App\Http\RequestContext;

/**
 * Профайлер временных промежутков (spans) текущего HTTP-запроса.
 *
 * Используется двумя способами:
 *  - Автоматически: Pipeline оборачивает каждый middleware, Router — вызов контроллера.
 *  - Вручную: app(Profiler::class)->start('my.block') / ->stop('my.block')
 *    или ->measure('my.block', fn() => ...) для любого произвольного кода.
 *
 * Все временны́е метки хранятся как смещение от старта RequestContext,
 * что позволяет отобразить
 * единую горизонтальную временну́ю шкалу всего запроса.
 */
final class Profiler
{
    /**
     * Незавершённые spans: имя → ['start' => float, 'category' => string].
     *
     * @var array<string, array{start: float, category: string}>
     */
    private array $pending = [];

    /**
     * Завершённые spans в хронологическом порядке.
     *
     * @var array<int, array{name: string, category: string, offset_ms: float, duration_ms: float}>
     */
    private array $spans = [];

    public function __construct(private readonly RequestContext $context)
    {
    }

    /**
     * Открывает новый span.
     *
     * @param string $name     Имя span — должно быть уникальным среди одновременно открытых.
     * @param string $category Категория для цветовой группировки в UI: middleware|controller|sql|app.
     */
    public function start(string $name, string $category = 'app'): void
    {
        $this->pending[$name] = ['start' => microtime(true), 'category' => $category];
    }

    /**
     * Закрывает ранее открытый span и сохраняет результат.
     * Игнорирует вызов, если span с таким именем не открыт.
     */
    public function stop(string $name): void
    {
        if (!isset($this->pending[$name])) {
            return;
        }

        $entry = $this->pending[$name];
        unset($this->pending[$name]);

        $this->spans[] = [
            'name'        => $name,
            'category'    => $entry['category'],
            'offset_ms'   => $this->context->offsetMs($entry['start']),
            'duration_ms' => round((microtime(true) - $entry['start']) * 1000, 3),
        ];
    }

    /**
     * Измеряет время выполнения callable и сохраняет span.
     * Безопасен для исключений — span закрывается через finally.
     *
     * @template T
     * @param  callable(): T $fn
     * @return T
     */
    public function measure(string $name, callable $fn, string $category = 'app'): mixed
    {
        $this->start($name, $category);
        try {
            return $fn();
        } finally {
            $this->stop($name);
        }
    }

    /**
     * Добавляет уже вычисленный span (используется Pipeline для pre-фазы middleware).
     *
     * @param float $startedAt Абсолютное время начала (microtime(true)).
     * @param float $endedAt   Абсолютное время конца.
     */
    public function addSpan(string $name, string $category, float $startedAt, float $endedAt): void
    {
        $this->spans[] = [
            'name'        => $name,
            'category'    => $category,
            'offset_ms'   => $this->context->offsetMs($startedAt),
            'duration_ms' => round(($endedAt - $startedAt) * 1000, 3),
        ];
    }

    /**
     * Возвращает все завершённые spans в порядке их появления.
     *
     * @return array<int, array{name: string, category: string, offset_ms: float, duration_ms: float}>
     */
    public function getSpans(): array
    {
        return $this->spans;
    }
}
