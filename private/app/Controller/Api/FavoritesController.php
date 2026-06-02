<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Request;
use App\Http\Response;
use App\I18n\Translator;
use App\Repository\CipherRepository;

/**
 * API-контроллер для получения данных об избранных сервисах.
 */
final class FavoritesController
{
    /**
     * Создаёт экземпляр контроллера избранного.
     */
    public function __construct(
        private readonly CipherRepository $ciphers,
        private readonly Translator $translator,
    ) {
    }

    /**
     * Возвращает данные шифров по массиву слагов из localStorage пользователя.
     *
     * GET /api/favorites/ciphers?slugs[]=classical-ciphers/caesar&slugs[]=encoding/base64
     */
    public function ciphers(Request $request): Response
    {
        $raw   = $request->query('slugs') ?? [];
        $slugs = is_array($raw) ? $raw : [];

        $slugs = array_values(array_slice(
            array_filter(
                $slugs,
                static fn (mixed $s): bool => is_string($s) && (bool) preg_match('/^[a-z0-9-]+\/[a-z0-9-]+$/', $s)
            ),
            0,
            20
        ));

        $defaultLanguage = $this->translator->getDefaultLocale();
        $localeParam     = $request->query('locale');
        $language        = (is_string($localeParam) && in_array($localeParam, $this->translator->getLocales(), true))
            ? $localeParam
            : $defaultLanguage;

        $localePrefix = ($language !== $defaultLanguage) ? '/' . $language : '';

        $ciphers = $this->ciphers->findPublishedBySlugsWithTranslation($slugs, $language, $defaultLanguage);

        $result = array_map(static fn (array $c): array => [
            'slug'        => $c['category_alias'].'/'.$c['alias'],
            'name'        => $c['name'],
            'name_short'  => $c['name_short'],
            'description' => $c['description_short'],
            'url'         => $localePrefix.'/'.$c['category_alias'].'/'.$c['alias'],
        ], $ciphers);

        return Response::json(['ciphers' => $result]);
    }
}
