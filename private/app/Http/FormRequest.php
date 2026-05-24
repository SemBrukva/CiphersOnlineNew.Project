<?php

declare(strict_types=1);

namespace App\Http;

use App\Validation\ValidationException;

/**
 * Базовый класс типизированного входного запроса.
 *
 * @phpstan-consistent-constructor
 */
abstract class FormRequest
{
    use InputValidatorTrait;

    /** @var array<string, mixed> Нормализованные данные запроса. */
    protected array $payload;

    /**
     * Создаёт экземпляр form request из HTTP-запроса.
     *
     * @throws ValidationException
     */
    public static function fromRequest(Request $request): static
    {
        $data = static::extractData($request);
        static::validateInput($data, static::rules());

        return new static($data, $request);
    }

    /**
     * Возвращает правила валидации для текущего DTO.
     *
     * @return array<string, string|array<int, string>>
     */
    abstract protected static function rules(): array;

    /**
     * Извлекает сырой набор входных данных из Request.
     *
     * @return array<string, mixed>
     */
    protected static function extractData(Request $request): array
    {
        $json = $request->json();

        if (is_array($json)) {
            return $json;
        }

        return $request->allInput();
    }

    /**
     * Создаёт DTO из валидированных данных.
     *
     * @param array<string, mixed> $payload
     */
    protected function __construct(array $payload, protected Request $request)
    {
        $this->payload = $payload;
    }

    /**
     * Возвращает значение поля payload.
     */
    protected function value(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }
}
