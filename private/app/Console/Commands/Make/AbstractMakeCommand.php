<?php

declare(strict_types=1);

namespace App\Console\Commands\Make;

use App\Console\CommandInterface;

/**
 * Базовая команда-генератор файлов по stub-шаблону.
 *
 * Подклассы задают тип, stub и логику формирования пути / замен плейсхолдеров.
 * Плейсхолдеры в stub: {{Class}}, {{Namespace}}, {{table}}, {{Description}}.
 */
abstract class AbstractMakeCommand implements CommandInterface
{
    /**
     * Возвращает тип создаваемого артефакта (используется в подсказках).
     */
    abstract protected function getType(): string;

    /**
     * Возвращает имя stub-файла относительно директории Stubs/.
     *
     * @param string $name Имя создаваемого артефакта (позволяет выбирать stub динамически).
     */
    abstract protected function getStub(string $name = ''): string;

    /**
     * Вычисляет абсолютный путь к создаваемому файлу.
     */
    abstract protected function getTargetPath(string $name): string;

    /**
     * Возвращает карту замен плейсхолдеров: ключ — плейсхолдер, значение — подстановка.
     *
     * @return array<string, string>
     */
    abstract protected function buildReplacements(string $name): array;

    /**
     * Читает stub, заменяет плейсхолдеры и записывает результат на диск.
     */
    public function handle(array $args): int
    {
        if ($args === []) {
            echo 'Использование: make:' . $this->getType() . ' <Имя>' . PHP_EOL;
            return 1;
        }

        $name   = $args[0];
        $target = $this->getTargetPath($name);

        if (file_exists($target)) {
            echo 'Файл уже существует: ' . $target . PHP_EOL;
            return 1;
        }

        $stubPath = APP_PATH . '/Console/Stubs/' . $this->getStub($name);
        $stub     = file_get_contents($stubPath);

        if ($stub === false) {
            echo 'Не найден stub: ' . $stubPath . PHP_EOL;
            return 1;
        }

        $replacements = $this->buildReplacements($name);
        $content      = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $dir = dirname($target);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($target, $content);
        echo 'Создан: ' . $target . PHP_EOL;

        return 0;
    }
}
