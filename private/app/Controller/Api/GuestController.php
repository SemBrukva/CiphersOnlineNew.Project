<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Auth\Auth;
use App\Cipher\ApiCipherToolRegistry;
use App\Controller\Api\Request\ContactRequest;
use App\Controller\Api\Request\LoginRequest;
use App\Controller\Api\Request\RegisterRequest;
use App\Event\EventDispatcherInterface;
use App\Event\Events\UserRegistered;
use App\Http\Attribute\ApiBody;
use App\Http\Attribute\ApiOperation;
use App\Http\Attribute\ApiResponse;
use App\Http\Attribute\Route;
use App\Http\Exception\NotFoundException;
use App\Http\Exception\UnauthorizedException;
use App\Http\Exception\ValidationFailedException;
use App\Http\Request;
use App\Http\Response;
use App\I18n\Translator;
use App\Repository\ContactRepository;
use App\Repository\UserRepository;

/**
 * Публичные API-эндпоинты, доступные без авторизации.
 */
final class GuestController
{
    /**
     * Создаёт экземпляр гостевого API-контроллера.
     */
    public function __construct(
        private readonly UserRepository $users,
        private readonly ContactRepository $contacts,
        private readonly Auth $auth,
        private readonly Translator $translator,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ApiCipherToolRegistry $cipherTools
    ) {
    }

    /**
     * Выполняет авторизацию пользователя по email и паролю.
     *
     * POST /api/auth/login
     */
    #[ApiOperation(summary: 'Авторизация', tags: ['auth'])]
    #[ApiBody(class: LoginRequest::class)]
    #[ApiResponse(status: 200, description: 'Авторизация успешна', schema: ['type' => 'object', 'properties' => ['ok' => ['type' => 'boolean', 'example' => true]]])]
    #[ApiResponse(status: 401, description: 'Неверный email или пароль')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    #[ApiResponse(status: 429, description: 'Превышен лимит запросов')]
    public function login(Request $request): Response
    {
        $dto = LoginRequest::fromRequest($request);

        if (!$this->auth->attempt($dto->email(), $dto->password())) {
            throw new UnauthorizedException((string) trans('AUTH_INVALID'));
        }

        return Response::json(['ok' => true]);
    }

    /**
     * Проверяет доступность API.
     *
     * GET /api/ping
     */
    #[Route(method: 'GET', path: '/ping', name: 'api.ping', group: 'api')]
    #[ApiOperation(summary: 'Проверка доступности API', tags: ['system'])]
    #[ApiResponse(status: 200, description: 'API доступен', schema: ['type' => 'object', 'properties' => ['pong' => ['type' => 'boolean'], 'time' => ['type' => 'integer']]])]
    public function ping(Request $request): Response
    {
        return Response::json([
            'pong' => true,
            'time' => time(),
        ]);
    }

    /**
     * Регистрирует нового пользователя.
     *
     * POST /api/auth/register
     */
    #[ApiOperation(summary: 'Регистрация пользователя', tags: ['auth'])]
    #[ApiBody(class: RegisterRequest::class)]
    #[ApiResponse(status: 201, description: 'Пользователь создан')]
    #[ApiResponse(status: 404, description: 'Регистрация отключена')]
    #[ApiResponse(status: 422, description: 'Email уже занят или ошибки валидации')]
    #[ApiResponse(status: 429, description: 'Превышен лимит запросов')]
    public function register(Request $request): Response
    {
        if (!config('app.user_registration', false)) {
            throw new NotFoundException('Registration is disabled.');
        }

        $dto = RegisterRequest::fromRequest($request);

        $name = $dto->name();
        $email = $dto->email();
        $password = $dto->password();
        $language = $dto->language() ?? $this->translator->getDefaultLocale();

        $locales = $this->translator->getLocales();
        if (!in_array($language, $locales, true)) {
            $language = $this->translator->getDefaultLocale();
        }

        if ($this->users->existsByEmail($email)) {
            throw new ValidationFailedException('The given data was invalid.', [
                'errors' => [
                    'email' => ['The email has already been taken.'],
                ],
            ]);
        }

        $verificationRequired = (bool) config('app.user_verification', false);
        $verificationToken = null;
        $verificationSentAt = null;
        $verifiedAt = null;

        if ($verificationRequired) {
            $verificationToken = bin2hex(random_bytes(32));
            $verificationSentAt = date('Y-m-d H:i:s');
        } else {
            $verifiedAt = date('Y-m-d H:i:s');
        }

        $userId = $this->users->insert([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'language' => $language,
            'email_verified_at' => $verifiedAt,
            'email_verification_token' => $verificationToken,
            'email_verification_sent_at' => $verificationSentAt,
        ]);

        $user = $this->users->find($userId);

        if (!$verificationRequired && $user !== null) {
            $this->auth->login($user);
        }

        if ($user !== null) {
            $this->dispatcher->dispatch(new UserRegistered($user, $verificationRequired));
        }

        return Response::json([
            'ok' => true,
            'verification_required' => $verificationRequired,
            'user' => [
                'id' => (int) $userId,
                'name' => $name,
                'email' => $email,
                'language' => $language,
                'email_verified' => !$verificationRequired,
            ],
        ], 201);
    }

    /**
     * Сохраняет сообщение из формы контактов.
     *
     * POST /api/contact
     */
    #[ApiOperation(summary: 'Отправка контактного сообщения', tags: ['contact'])]
    #[ApiBody(class: ContactRequest::class)]
    #[ApiResponse(status: 201, description: 'Сообщение сохранено', schema: ['type' => 'object', 'properties' => ['ok' => ['type' => 'boolean', 'example' => true]]])]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    #[ApiResponse(status: 429, description: 'Превышен лимит запросов')]
    public function contact(Request $request): Response
    {
        $dto = ContactRequest::fromRequest($request);

        $userId = $this->auth->id();
        $ip = mb_substr($request->ip(), 0, 45);

        $this->contacts->create(
            $userId !== null ? (int) $userId : null,
            $dto->name(),
            $dto->email(),
            $dto->message(),
            $ip
        );

        return Response::json(['ok' => true], 201);
    }

    /**
     * Выполняет шифрование/дешифрование Цезаря через API.
     *
     * POST /api/tools/caesar
     */
    #[ApiOperation(summary: 'Шифр Цезаря', tags: ['tools'])]
    #[ApiResponse(status: 200, description: 'Результат обработки')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    public function caesar(Request $request): Response
    {
        return $this->handleCipherTool($request, 'caesar');
    }

    /**
     * Выполняет шифрование/дешифрование Плейфера через API.
     *
     * POST /api/tools/playfair
     */
    #[ApiOperation(summary: 'Шифр Плейфера', tags: ['tools'])]
    #[ApiResponse(status: 200, description: 'Результат обработки')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    public function playfair(Request $request): Response
    {
        return $this->handleCipherTool($request, 'playfair');
    }

    /**
     * Выполняет шифрование/дешифрование Бофора через API.
     *
     * POST /api/tools/beaufort
     */
    #[ApiOperation(summary: 'Шифр Бофора', tags: ['tools'])]
    #[ApiResponse(status: 200, description: 'Результат обработки')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    public function beaufort(Request $request): Response
    {
        return $this->handleCipherTool($request, 'beaufort');
    }

    /**
     * Выполняет шифрование/дешифрование Гронсфельда через API.
     *
     * POST /api/tools/gronsfeld
     */
    #[ApiOperation(summary: 'Шифр Гронсфельда', tags: ['tools'])]
    #[ApiResponse(status: 200, description: 'Результат обработки')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    public function gronsfeld(Request $request): Response
    {
        return $this->handleCipherTool($request, 'gronsfeld');
    }

    /**
     * Выполняет шифрование/дешифрование Виженера через API.
     *
     * POST /api/tools/vigenere
     */
    #[ApiOperation(summary: 'Шифр Виженера', tags: ['tools'])]
    #[ApiResponse(status: 200, description: 'Результат обработки')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    public function vigenere(Request $request): Response
    {
        return $this->handleCipherTool($request, 'vigenere');
    }

    /**
     * Выполняет шифрование/дешифрование Вернама через API.
     *
     * POST /api/tools/vernam
     */
    #[ApiOperation(summary: 'Шифр Вернама', tags: ['tools'])]
    #[ApiResponse(status: 200, description: 'Результат обработки')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    public function vernam(Request $request): Response
    {
        return $this->handleCipherTool($request, 'vernam');
    }

    /**
     * Выполняет шифрование/дешифрование Бэкона через API.
     *
     * POST /api/tools/bacon
     */
    #[ApiOperation(summary: 'Шифр Бэкона', tags: ['tools'])]
    #[ApiResponse(status: 200, description: 'Результат обработки')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    public function bacon(Request $request): Response
    {
        return $this->handleCipherTool($request, 'bacon');
    }

    /**
     * Выполняет API-инструмент шифрования и формирует JSON-ответ.
     */
    private function handleCipherTool(Request $request, string $action): Response
    {
        $payload = $request->json();
        if (!is_array($payload)) {
            $payload = [];
        }

        return Response::json($this->cipherTools->execute($action, $payload));
    }
}
