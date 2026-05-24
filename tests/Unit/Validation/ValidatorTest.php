<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Validation\RuleInterface;
use App\Validation\ValidationException;
use App\Validation\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет поведение валидатора и встроенных правил.
 */
final class ValidatorTest extends TestCase
{
    /**
     * Проверяет успешную валидацию корректных данных.
     */
    public function testPassesForValidData(): void
    {
        $validator = new Validator(
            ['email' => 'john@example.com', 'age' => 20],
            ['email' => 'required|email', 'age' => 'integer|min:18']
        );

        self::assertTrue($validator->passes());
        self::assertFalse($validator->fails());
        self::assertSame([], $validator->errors());
    }

    /**
     * Проверяет ошибку правила required для пустого значения.
     */
    public function testFailsWhenRequiredFieldIsEmpty(): void
    {
        $validator = new Validator(['name' => ''], ['name' => 'required']);

        self::assertTrue($validator->fails());
        self::assertArrayHasKey('name', $validator->errors());
    }

    /**
     * Проверяет, что пустое поле без required пропускает остальные правила.
     */
    public function testSkipsNonRequiredRulesForEmptyValue(): void
    {
        $validator = new Validator(['name' => ''], ['name' => 'string|min:3']);

        self::assertTrue($validator->passes());
    }

    /**
     * Проверяет правило in с допустимым и недопустимым значением.
     */
    public function testInRuleWorksCorrectly(): void
    {
        $valid = new Validator(['role' => 'admin'], ['role' => 'in:user,admin']);
        $invalid = new Validator(['role' => 'root'], ['role' => 'in:user,admin']);

        self::assertTrue($valid->passes());
        self::assertTrue($invalid->fails());
    }

    /**
     * Проверяет выброс ValidationException методом validate().
     */
    public function testValidateThrowsValidationExceptionOnFailure(): void
    {
        $validator = new Validator(['email' => 'bad'], ['email' => 'email']);

        $this->expectException(ValidationException::class);
        $validator->validate();
    }

    /**
     * Проверяет поддержку объектных правил RuleInterface.
     */
    public function testSupportsCustomRuleObject(): void
    {
        $rule = new class () implements RuleInterface {
            /**
             * Проверяет валидность значения.
             */
            public function validate(string $field, mixed $value, array $data): bool
            {
                return $value === 'ok';
            }

            /**
             * Возвращает сообщение об ошибке.
             */
            public function message(string $field): string
            {
                return 'custom error';
            }
        };

        $validator = new Validator(['code' => 'ok'], ['code' => [$rule]]);

        self::assertTrue($validator->passes());
    }

    /**
     * Проверяет исключение для неизвестного имени правила.
     */
    public function testThrowsForUnknownRule(): void
    {
        $validator = new Validator(['name' => 'john'], ['name' => 'unknown']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown validation rule: [unknown]');

        $validator->passes();
    }

    /**
     * Проверяет, что после первой валидации результат кешируется.
     */
    public function testValidationRunsOnlyOnceAndKeepsSameErrors(): void
    {
        $validator = new Validator(['email' => 'bad'], ['email' => 'email']);

        $first = $validator->errors();
        $second = $validator->errors();

        self::assertSame($first, $second);
    }
}
