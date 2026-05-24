<?php

declare(strict_types=1);

namespace App\OpenApi;

use App\Http\Attribute\ApiBody;
use App\Http\Attribute\ApiOperation;
use App\Http\Attribute\ApiParam;
use App\Http\Attribute\ApiResponse;
use App\Http\Middleware\ApiAdminMiddleware;
use App\Http\Middleware\ApiAuthMiddleware;
use ReflectionException;
use ReflectionMethod;

/**
 * Генерирует спецификацию OpenAPI 3.0 из таблицы API-маршрутов и PHP-атрибутов контроллеров.
 */
final class OpenApiGenerator
{
    /**
     * Генерирует массив спецификации OpenAPI 3.0.3.
     *
     * @param array<string, array<string, mixed>> $apiRoutes Скомпилированные API-маршруты (с /api-префиксом).
     * @return array<string, mixed>
     */
    public function generate(array $apiRoutes): array
    {
        $spec = $this->buildBaseSpec();

        foreach ($apiRoutes as $routeKey => $route) {
            [$httpMethod, $rawPath] = explode(' ', $routeKey, 2);

            $path = $this->normalizePathParams($rawPath);
            $method = strtolower($httpMethod);

            $spec['paths'][$path][$method] = $this->buildOperation($route, $path);
        }

        ksort($spec['paths']);

        return $spec;
    }

    /**
     * Строит базовую структуру спецификации.
     *
     * @return array<string, mixed>
     */
    private function buildBaseSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => (string) config('app.name', 'API'),
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => rtrim((string) config('app.url', 'http://localhost'), '/')],
            ],
            'paths' => [],
            'components' => [
                'securitySchemes' => [
                    'sessionAuth' => [
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => 'PHPSESSID',
                        'description' => 'Аутентификация через сессионную cookie.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Строит объект операции OpenAPI для одного маршрута.
     *
     * @param array<string, mixed> $route
     * @return array<string, mixed>
     */
    private function buildOperation(array $route, string $path): array
    {
        $controllerClass = (string) ($route['controller'] ?? '');
        $controllerMethod = (string) ($route['method'] ?? '');
        $middleware = (array) ($route['middleware'] ?? []);

        $operation = [
            'summary' => '',
            'tags' => [],
            'parameters' => [],
            'responses' => [],
        ];

        if (isset($route['name']) && $route['name'] !== '') {
            $operation['operationId'] = (string) $route['name'];
        }

        if ($controllerClass !== '' && $controllerMethod !== '' && class_exists($controllerClass)) {
            try {
                $reflection = new ReflectionMethod($controllerClass, $controllerMethod);
                $this->applyAttributes($operation, $reflection);
            } catch (ReflectionException) {
            }
        }

        $this->fillMissingPathParams($operation, $path);
        $this->applySecurityFromMiddleware($operation, $middleware);

        if ($operation['responses'] === []) {
            $operation['responses']['200'] = ['description' => 'OK'];
        }

        if ($operation['parameters'] === []) {
            unset($operation['parameters']);
        }

        if ($operation['tags'] === []) {
            unset($operation['tags']);
        }

        return $operation;
    }

    /**
     * Читает PHP-атрибуты метода контроллера и применяет их к операции.
     *
     * @param array<string, mixed> $operation
     */
    private function applyAttributes(array &$operation, ReflectionMethod $method): void
    {
        foreach ($method->getAttributes(ApiOperation::class) as $attr) {
            /** @var ApiOperation $op */
            $op = $attr->newInstance();

            if ($op->summary !== '') {
                $operation['summary'] = $op->summary;
            }

            if ($op->description !== '') {
                $operation['description'] = $op->description;
            }

            if ($op->tags !== []) {
                $operation['tags'] = $op->tags;
            }

            if ($op->deprecated) {
                $operation['deprecated'] = true;
            }
        }

        foreach ($method->getAttributes(ApiParam::class) as $attr) {
            /** @var ApiParam $p */
            $p = $attr->newInstance();

            $param = [
                'name' => $p->name,
                'in' => $p->in,
                'required' => $p->required,
                'schema' => ['type' => $p->type],
            ];

            if ($p->description !== '') {
                $param['description'] = $p->description;
            }

            if ($p->example !== null) {
                $param['example'] = $p->example;
            }

            $operation['parameters'][] = $param;
        }

        foreach ($method->getAttributes(ApiBody::class) as $attr) {
            /** @var ApiBody $b */
            $b = $attr->newInstance();
            $operation['requestBody'] = $this->buildRequestBody($b);
        }

        foreach ($method->getAttributes(ApiResponse::class) as $attr) {
            /** @var ApiResponse $r */
            $r = $attr->newInstance();

            $response = [
                'description' => $r->description !== '' ? $r->description : $this->defaultStatusText($r->status),
            ];

            if ($r->schema !== []) {
                $response['content'] = [
                    'application/json' => ['schema' => $r->schema],
                ];
            }

            $operation['responses'][(string) $r->status] = $response;
        }
    }

    /**
     * Строит объект requestBody из атрибута #[ApiBody].
     *
     * @return array<string, mixed>
     */
    private function buildRequestBody(ApiBody $attr): array
    {
        $body = ['required' => $attr->required];

        if ($attr->description !== '') {
            $body['description'] = $attr->description;
        }

        $schema = ['type' => 'object'];

        if ($attr->class !== '' && class_exists($attr->class)) {
            try {
                $rulesMethod = new ReflectionMethod($attr->class, 'rules');
                $rulesMethod->setAccessible(true);
                $rules = $rulesMethod->invoke(null);

                if (is_array($rules)) {
                    $schema = $this->inferSchemaFromRules($rules);
                }
            } catch (ReflectionException) {
            }
        }

        $body['content'] = ['application/json' => ['schema' => $schema]];

        return $body;
    }

    /**
     * Выводит OpenAPI-схему из правил валидации FormRequest.
     *
     * @param  array<string, string|string[]> $rules
     * @return array<string, mixed>
     */
    private function inferSchemaFromRules(array $rules): array
    {
        $properties = [];
        $required = [];

        foreach ($rules as $field => $ruleValue) {
            $parts = is_array($ruleValue) ? $ruleValue : explode('|', $ruleValue);

            $prop = ['type' => 'string'];
            $isRequired = false;

            foreach ($parts as $rule) {
                if ($rule === 'required') {
                    $isRequired = true;
                } elseif ($rule === 'integer') {
                    $prop['type'] = 'integer';
                } elseif ($rule === 'numeric') {
                    $prop['type'] = 'number';
                } elseif ($rule === 'boolean') {
                    $prop['type'] = 'boolean';
                } elseif ($rule === 'email') {
                    $prop['format'] = 'email';
                } elseif ($rule === 'url') {
                    $prop['format'] = 'uri';
                } elseif (str_starts_with($rule, 'min:')) {
                    $n = (int) substr($rule, 4);
                    if ($prop['type'] === 'string') {
                        $prop['minLength'] = $n;
                    } else {
                        $prop['minimum'] = $n;
                    }
                } elseif (str_starts_with($rule, 'max:')) {
                    $n = (int) substr($rule, 4);
                    if ($prop['type'] === 'string') {
                        $prop['maxLength'] = $n;
                    } else {
                        $prop['maximum'] = $n;
                    }
                } elseif (str_starts_with($rule, 'in:')) {
                    $prop['enum'] = explode(',', substr($rule, 3));
                }
            }

            $properties[$field] = $prop;

            if ($isRequired) {
                $required[] = $field;
            }
        }

        $schema = ['type' => 'object', 'properties' => $properties];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * Добавляет параметры пути, не объявленные через #[ApiParam].
     *
     * @param array<string, mixed> $operation
     */
    private function fillMissingPathParams(array &$operation, string $path): void
    {
        preg_match_all('/\{([a-z_][a-z0-9_]*)\}/i', $path, $matches);

        $existingNames = array_column($operation['parameters'], 'name');

        foreach ($matches[1] as $name) {
            if (in_array($name, $existingNames, true)) {
                continue;
            }

            $operation['parameters'][] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }
    }

    /**
     * Добавляет требование аутентификации, если маршрут защищён auth-middleware.
     *
     * @param array<string, mixed> $operation
     * @param string[]             $middleware
     */
    private function applySecurityFromMiddleware(array &$operation, array $middleware): void
    {
        foreach ($middleware as $mw) {
            if ($mw === ApiAuthMiddleware::class || $mw === ApiAdminMiddleware::class) {
                $operation['security'] = [['sessionAuth' => []]];
                return;
            }
        }
    }

    /**
     * Убирает кастомные regex-ограничения из параметров пути.
     * Пример: /user/{id:\d+} → /user/{id}
     */
    private function normalizePathParams(string $path): string
    {
        return preg_replace('/\{([a-z_][a-z0-9_]*):[^}]+\}/i', '{$1}', $path) ?? $path;
    }

    /**
     * Возвращает стандартное описание для HTTP-статуса.
     */
    private function defaultStatusText(int $status): string
    {
        return match ($status) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            default => 'Response',
        };
    }
}
