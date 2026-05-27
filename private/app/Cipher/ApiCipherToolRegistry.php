<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\NotFoundException;

/**
 * Реестр API-инструментов шифрования с доступом по action.
 */
final class ApiCipherToolRegistry
{
    /** @var array<string, ApiCipherToolInterface> Карта action -> инструмент. */
    private array $tools = [];

    /**
     * Создаёт экземпляр реестра API-инструментов.
     */
    public function __construct(
        CaesarApiCipherTool $caesarTool,
        AtbashApiCipherTool $atbashTool,
        PlayfairApiCipherTool $playfairTool,
        BeaufortApiCipherTool $beaufortTool,
        GronsfeldApiCipherTool $gronsfeldTool,
        VigenereApiCipherTool $vigenereTool,
        VernamApiCipherTool $vernamTool,
        BaconApiCipherTool $baconTool,
        A1z26ApiCipherTool $a1z26Tool
    ) {
        foreach ([$caesarTool, $atbashTool, $playfairTool, $beaufortTool, $gronsfeldTool, $vigenereTool, $vernamTool, $baconTool, $a1z26Tool] as $tool) {
            $this->tools[$tool->action()] = $tool;
        }
    }

    /**
     * Выполняет инструмент по action и возвращает результат.
     *
     * @param  string              $action  Идентификатор инструмента.
     * @param  array<string, mixed> $payload Входные данные запроса.
     * @return array<string, mixed>
     */
    public function execute(string $action, array $payload): array
    {
        $tool = $this->tools[$action] ?? null;
        if ($tool === null) {
            throw new NotFoundException('Tool not found.');
        }

        return $tool->execute($payload);
    }
}
