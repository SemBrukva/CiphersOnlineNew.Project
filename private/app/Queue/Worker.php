<?php

declare(strict_types=1);

namespace App\Queue;

use App\Container\Container;
use App\Log\LoggerInterface;
use Throwable;

/**
 * Исполнитель задач очереди.
 *
 * Циклически опрашивает очередь, десериализует задачу, выполняет её
 * и обрабатывает результат (удаление, повторная попытка или перенос в failed_jobs).
 */
final class Worker
{
    /** @var bool Флаг graceful-остановки воркера. */
    private bool $shouldStop = false;

    /**
     * Создаёт исполнитель задач.
     */
    public function __construct(
        private readonly QueueManager $queue,
        private readonly Container $container,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Запускает основной цикл воркера.
     *
     * @param string $queueName  Имя очереди для обработки.
     * @param int    $sleep      Пауза при отсутствии задач, секунды.
     * @param int    $maxJobs    Лимит задач за запуск (0 — без лимита).
     * @param int    $maxSeconds Лимит времени работы воркера в секундах (0 — без лимита).
     */
    public function run(string $queueName, int $sleep = 3, int $maxJobs = 0, int $maxSeconds = 0): void
    {
        $this->registerSignalHandlers();

        $startedAt = time();
        $processed = 0;

        while (!$this->shouldStop) {
            $job = $this->queue->pop($queueName);

            if ($job === null) {
                if ($this->reachedLimits($startedAt, $processed, $maxJobs, $maxSeconds)) {
                    return;
                }

                $this->dispatchSignals();
                sleep(max(1, $sleep));
                continue;
            }

            $this->process($job);
            $processed++;

            if ($this->reachedLimits($startedAt, $processed, $maxJobs, $maxSeconds)) {
                return;
            }

            $this->dispatchSignals();
        }
    }

    /**
     * Сигнализирует воркеру о необходимости завершиться после текущей итерации.
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * Выполняет одну задачу из очереди и обрабатывает результат.
     *
     * @param array{id: int, queue: string, payload: string, attempts: int} $job
     */
    private function process(array $job): void
    {
        try {
            $instance = $this->unserializeJob($job['payload']);
            $this->invoke($instance);
            $this->queue->delete($job['id']);

            $this->logger->info('Queue job processed: {class}', [
                'class'   => $instance::class,
                'job_id'  => $job['id'],
                'queue'   => $job['queue'],
                'attempts' => $job['attempts'],
            ]);
        } catch (Throwable $e) {
            $this->handleFailure($job, $e);
        }
    }

    /**
     * Обрабатывает упавшую задачу: повторная попытка или перенос в failed_jobs.
     *
     * @param array{id: int, queue: string, payload: string, attempts: int} $job
     */
    private function handleFailure(array $job, Throwable $exception): void
    {
        if ($job['attempts'] >= $this->queue->maxAttempts()) {
            $this->queue->markFailed($job['id'], $job['queue'], $job['payload'], $exception);

            $this->logger->error('Queue job failed permanently: {error}', [
                'error'    => $exception->getMessage(),
                'job_id'   => $job['id'],
                'queue'    => $job['queue'],
                'attempts' => $job['attempts'],
            ]);

            return;
        }

        $this->queue->release($job['id'], $this->queue->retryAfter());

        $this->logger->warning('Queue job failed, will retry: {error}', [
            'error'    => $exception->getMessage(),
            'job_id'   => $job['id'],
            'queue'    => $job['queue'],
            'attempts' => $job['attempts'],
        ]);
    }

    /**
     * Десериализует payload в экземпляр JobInterface.
     *
     * Использует явный вайтлист классов из queue.job_classes для защиты от
     * object-injection: PHP не будет вызывать __wakeup/__destruct классов
     * вне списка, даже если payload был подменён в БД.
     */
    private function unserializeJob(string $payload): JobInterface
    {
        /** @var class-string[] $allowed */
        $allowed  = (array) config('queue.job_classes', []);
        $instance = @unserialize($payload, ['allowed_classes' => $allowed]);

        if (!$instance instanceof JobInterface) {
            throw new \RuntimeException('Queue payload must unserialize to a JobInterface instance.');
        }

        return $instance;
    }

    /**
     * Выполняет задачу, при необходимости резолвя её зависимости через контейнер.
     */
    private function invoke(JobInterface $job): void
    {
        if ($job instanceof ContainerAwareJobInterface) {
            $job->setContainer($this->container);
        }

        $job->handle();
    }

    /**
     * Регистрирует обработчики SIGTERM/SIGINT для graceful-остановки.
     */
    private function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $this->stop());
        pcntl_signal(SIGINT, fn () => $this->stop());
    }

    /**
     * Передаёт управление зарегистрированным обработчикам сигналов, если pcntl доступен.
     */
    private function dispatchSignals(): void
    {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * Проверяет, достигнут ли один из лимитов воркера.
     */
    private function reachedLimits(int $startedAt, int $processed, int $maxJobs, int $maxSeconds): bool
    {
        if ($maxJobs > 0 && $processed >= $maxJobs) {
            return true;
        }

        if ($maxSeconds > 0 && (time() - $startedAt) >= $maxSeconds) {
            return true;
        }

        return false;
    }
}
