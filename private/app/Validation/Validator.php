<?php

declare(strict_types=1);

namespace App\Validation;

use App\Validation\Rules\Email;
use App\Validation\Rules\In;
use App\Validation\Rules\IsBoolean;
use App\Validation\Rules\IsInteger;
use App\Validation\Rules\IsString;
use App\Validation\Rules\Max;
use App\Validation\Rules\Min;
use App\Validation\Rules\Numeric;
use App\Validation\Rules\Required;
use App\Validation\Rules\Url;
use InvalidArgumentException;

/**
 * Валидатор входных данных по набору правил.
 *
 * Правила задаются строкой через «|» («required|string|min:3») или массивом
 * объектов RuleInterface. Пустые поля пропускают все правила, кроме required.
 */
final class Validator
{
    /** @var array<string, string[]> Накопленные ошибки валидации по полям. */
    private array $errors    = [];

    /** @var bool Признак того, что валидация уже была запущена. */
    private bool  $validated = false;

    /**
     * Создаёт экземпляр валидатора.
     *
     * @param array<string, mixed>             $data  Данные для проверки.
     * @param array<string, string|string[]|RuleInterface[]> $rules Правила по полям.
     */
    public function __construct(
        private readonly array $data,
        private readonly array $rules
    ) {
    }

    /**
     * Запускает валидацию и возвращает true, если ошибок нет.
     */
    public function passes(): bool
    {
        $this->run();

        return empty($this->errors);
    }

    /**
     * Возвращает true, если валидация завершилась с ошибками.
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Возвращает карту ошибок валидации: поле → список сообщений.
     *
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        $this->run();

        return $this->errors;
    }

    /**
     * Запускает валидацию и выбрасывает ValidationException при наличии ошибок.
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }
    }

    /**
     * Выполняет проход по всем полям и правилам; повторный вызов безопасен.
     */
    private function run(): void
    {
        if ($this->validated) {
            return;
        }

        $this->validated = true;
        $this->errors    = [];

        foreach ($this->rules as $field => $fieldRules) {
            $rules   = $this->parseRules($fieldRules);
            $value   = $this->data[$field] ?? null;
            $isEmpty = $this->isEmpty($value);

            foreach ($rules as $rule) {
                if ($isEmpty && !($rule instanceof Required)) {
                    continue;
                }

                if (!$rule->validate($field, $value, $this->data)) {
                    $this->errors[$field][] = $rule->message($field);
                    break;
                }
            }
        }
    }

    /**
     * Проверяет, является ли значение «пустым» для целей валидации.
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Преобразует строку или массив правил в массив объектов RuleInterface.
     *
     * @param  string|array<int, string|RuleInterface> $rules
     * @return RuleInterface[]
     */
    private function parseRules(array|string $rules): array
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        return array_map(function (mixed $rule): RuleInterface {
            if ($rule instanceof RuleInterface) {
                return $rule;
            }

            $parts = explode(':', (string) $rule, 2);
            return $this->makeRule($parts[0], $parts[1] ?? null);
        }, $rules);
    }

    /**
     * Создаёт объект правила по его строковому имени и необязательному параметру.
     *
     * @throws InvalidArgumentException Если правило с таким именем не зарегистрировано.
     */
    private function makeRule(string $name, ?string $param): RuleInterface
    {
        return match ($name) {
            'required' => new Required(),
            'string'   => new IsString(),
            'integer'  => new IsInteger(),
            'numeric'  => new Numeric(),
            'boolean'  => new IsBoolean(),
            'email'    => new Email(),
            'url'      => new Url(),
            'min'      => new Min((float) $param),
            'max'      => new Max((float) $param),
            'in'       => new In(explode(',', $param ?? '')),
            default    => throw new InvalidArgumentException("Unknown validation rule: [{$name}]"),
        };
    }
}
