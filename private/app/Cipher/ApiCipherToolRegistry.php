<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\NotFoundException;

/**
 * Реестр API-инструментов шифрования с доступом по action.
 */
final class ApiCipherToolRegistry implements ApiCipherToolExecutorInterface
{
    /** @var array<string, ApiCipherToolInterface> Карта action -> инструмент. */
    private array $tools = [];

    /**
     * Создаёт экземпляр реестра API-инструментов.
     */
    public function __construct(
        AffineApiCipherTool $affineTool,
        CaesarApiCipherTool $caesarTool,
        AtbashApiCipherTool $atbashTool,
        PlayfairApiCipherTool $playfairTool,
        BeaufortApiCipherTool $beaufortTool,
        PortaApiCipherTool $portaTool,
        AutokeyApiCipherTool $autokeyTool,
        GronsfeldApiCipherTool $gronsfeldTool,
        VigenereApiCipherTool $vigenereTool,
        VernamApiCipherTool $vernamTool,
        BaconApiCipherTool $baconTool,
        Rot13ApiCipherTool $rot13Tool,
        A1z26ApiCipherTool $a1z26Tool,
        RailFenceApiCipherTool $railFenceTool,
        ColumnarTranspositionApiCipherTool $columnarTranspositionTool,
        PolybiusSquareApiCipherTool $polybiusSquareTool,
        HillApiCipherTool $hillTool,
        CaesarBruteForceApiCipherTool $caesarBruteForceTool,
        AffineBruteForceApiCipherTool $affineBruteForceTool,
        SimpleSubstitutionApiCipherTool $simpleSubstitutionTool,
        XorApiCipherTool $xorTool,
        VigenereCrackerApiCipherTool $vigenereCrackerTool,
        BifidApiCipherTool $bifidTool,
        TrifidApiCipherTool $trifidTool,
        AlbertiApiCipherTool $albertiTool,
    ) {
        foreach ([$affineTool, $caesarTool, $atbashTool, $playfairTool, $beaufortTool, $portaTool, $autokeyTool, $gronsfeldTool, $vigenereTool, $vernamTool, $baconTool, $rot13Tool, $a1z26Tool, $railFenceTool, $columnarTranspositionTool, $polybiusSquareTool, $hillTool, $caesarBruteForceTool, $affineBruteForceTool, $simpleSubstitutionTool, $xorTool, $vigenereCrackerTool, $bifidTool, $trifidTool, $albertiTool] as $tool) {
            $this->tools[$tool->action()] = $tool;
        }
    }

    /**
     * Регистрирует дополнительный инструмент после создания реестра.
     * Используется для разрыва циклической зависимости при DI.
     */
    public function register(ApiCipherToolInterface $tool): void
    {
        $this->tools[$tool->action()] = $tool;
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
