<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Queue\QueueManager;
use App\Queue\Worker;

/**
 * Консольная команда запуска воркера очереди.
 *
 * Использование:
 *   php bin/console queue:work [--queue=default] [--sleep=3] [--max-jobs=0] [--max-time=0]
 */
final readonly class QueueWorkCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Worker $worker)
    {
    }

    /**
     * Запускает воркер с переданными опциями.
     *
     * @param string[] $args
     */
    public function handle(array $args): int
    {
        $options = $this->parseOptions($args);

        $queue = $options['queue'] ?? QueueManager::DEFAULT_QUEUE;
        $sleep = (int) ($options['sleep'] ?? 3);
        $maxJobs = (int) ($options['max-jobs'] ?? 0);
        $maxTime = (int) ($options['max-time'] ?? 0);

        echo "Queue worker started (queue={$queue}, sleep={$sleep}s)" . PHP_EOL;
        echo 'Press Ctrl+C to stop.' . PHP_EOL;

        $this->worker->run($queue, $sleep, $maxJobs, $maxTime);

        echo 'Queue worker stopped.' . PHP_EOL;

        return 0;
    }

    /**
     * Разбирает аргументы вида --key=value в ассоциативный массив.
     *
     * @param  string[]              $args
     * @return array<string, string>
     */
    private function parseOptions(array $args): array
    {
        $options = [];

        foreach ($args as $arg) {
            if (!str_starts_with($arg, '--')) {
                continue;
            }

            $body = substr($arg, 2);
            $pos = strpos($body, '=');

            if ($pos === false) {
                $options[$body] = '1';
                continue;
            }

            $options[substr($body, 0, $pos)] = substr($body, $pos + 1);
        }

        return $options;
    }
}
