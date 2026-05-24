<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherRepository;
use App\View\View;

/**
 * Контроллер управления шифрами в административной панели.
 */
final class CipherController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private readonly View $view,
        private readonly CipherRepository $ciphers,
        private readonly CipherCategoryRepository $categories,
        private readonly Session $session
    ) {
    }

    /**
     * Отображает список шифров.
     */
    public function index(Request $request): Response
    {
        $adminPath = (string) config('admin.path', '/admin');
        $availableLanguages = $this->availableLanguages();

        $this->view
            ->setTitle('Шифры')
            ->setBreadcrumbs([['label' => 'Шифры']])
            ->setContent($this->view->fetch('admin/ciphers/index.tpl', [
                'ciphers' => $this->ciphers->listForAdmin(),
                'cipher_languages' => $this->ciphers->listLanguageMapByCipher(),
                'available_languages' => $availableLanguages,
                'admin_path' => $adminPath,
                'success' => $this->session->getFlash('success'),
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Отображает форму создания шифра.
     */
    public function create(Request $request): Response
    {
        $adminPath = (string) config('admin.path', '/admin');

        $this->view
            ->setTitle('Добавить шифр')
            ->setBreadcrumbs([
                ['label' => 'Шифры', 'url' => $adminPath . '/ciphers'],
                ['label' => 'Добавить шифр'],
            ])
            ->setContent($this->view->fetch('admin/ciphers/form.tpl', [
                'cipher' => null,
                'categories' => $this->categories->listForSelect(),
                'admin_path' => $adminPath,
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Сохраняет новый шифр.
     */
    public function store(Request $request): Response
    {
        $adminPath = (string) config('admin.path', '/admin');

        $alias = mb_strtolower(trim((string) $request->input('alias', '')));
        $categoryId = (int) $request->input('category_id', 0);
        $sortOrder = (int) $request->input('sort_order', 0);
        $published = $request->input('published') !== null ? 1 : 0;

        $error = $this->validateCipherInput($alias, $categoryId, $sortOrder, null);

        if ($error !== null) {
            $this->session->flash('error', $error);

            return new Response('', 302, ['Location' => $adminPath . '/ciphers/create']);
        }

        $now = date('Y-m-d H:i:s');
        $id = $this->ciphers->insert([
            'category_id' => $categoryId,
            'alias' => $alias,
            'sort_order' => $sortOrder,
            'published' => $published,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->session->flash('success', 'Шифр добавлен.');

        return new Response('', 302, ['Location' => $adminPath . '/ciphers/' . $id . '/edit']);
    }

    /**
     * Отображает страницу редактирования шифра и его локализованного контента.
     */
    public function edit(Request $request): Response
    {
        $adminPath = (string) config('admin.path', '/admin');
        $id = (int) $request->route('id');
        $availableLanguages = $this->availableLanguages();

        $activeCipher = $this->buildCipherPayload($id, $availableLanguages);

        if ($activeCipher === null) {
            $this->session->flash('error', 'Шифр не найден.');

            return new Response('', 302, ['Location' => $adminPath . '/ciphers']);
        }

        $this->view
            ->setTitle('Редактировать шифр')
            ->setBreadcrumbs([
                ['label' => 'Шифры', 'url' => $adminPath . '/ciphers'],
                ['label' => 'Редактировать шифр'],
            ])
            ->setContent($this->view->fetch('admin/ciphers/edit.tpl', [
                'active_cipher' => $activeCipher,
                'categories' => $this->categories->listForSelect(),
                'available_languages' => $availableLanguages,
                'active_language' => mb_strtolower((string) $request->query('language', '')),
                'admin_path' => $adminPath,
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Обновляет базовые поля шифра через обычную форму.
     */
    public function update(Request $request): Response
    {
        $adminPath = (string) config('admin.path', '/admin');
        $id = (int) $request->route('id');

        if ($this->ciphers->find($id) === null) {
            $this->session->flash('error', 'Шифр не найден.');

            return new Response('', 302, ['Location' => $adminPath . '/ciphers']);
        }

        $alias = mb_strtolower(trim((string) $request->input('alias', '')));
        $categoryId = (int) $request->input('category_id', 0);
        $sortOrder = (int) $request->input('sort_order', 0);
        $published = $request->input('published') !== null ? 1 : 0;

        $error = $this->validateCipherInput($alias, $categoryId, $sortOrder, $id);

        if ($error !== null) {
            $this->session->flash('error', $error);

            return new Response('', 302, ['Location' => $adminPath . '/ciphers/' . $id . '/edit']);
        }

        $this->ciphers->update($id, [
            'category_id' => $categoryId,
            'alias' => $alias,
            'sort_order' => $sortOrder,
            'published' => $published,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->session->flash('success', 'Шифр обновлён.');

        return new Response('', 302, ['Location' => $adminPath . '/ciphers/' . $id . '/edit']);
    }

    /**
     * Удаляет шифр.
     */
    public function destroy(Request $request): Response
    {
        $adminPath = (string) config('admin.path', '/admin');
        $id = (int) $request->route('id');

        $this->ciphers->delete($id);
        $this->session->flash('success', 'Шифр удалён.');

        return new Response('', 302, ['Location' => $adminPath . '/ciphers']);
    }

    /**
     * Валидирует базовые поля шифра и возвращает текст ошибки при провале.
     */
    private function validateCipherInput(string $alias, int $categoryId, int $sortOrder, ?int $exceptId): ?string
    {
        if ($alias === '' || !preg_match('/^[a-z0-9-]{2,100}$/', $alias)) {
            return 'Alias должен содержать 2-100 символов: a-z, 0-9 и дефис.';
        }

        if ($categoryId < 1) {
            return 'Выберите категорию.';
        }

        if ($sortOrder < 0 || $sortOrder > 999999) {
            return 'Порядок сортировки должен быть от 0 до 999999.';
        }

        if ($this->ciphers->existsByAlias($alias, $exceptId)) {
            return 'Шифр с таким alias уже существует.';
        }

        return null;
    }

    /**
     * Собирает полный payload шифра для шаблона редактирования.
     *
     * @param  int      $cipherId            ID шифра.
     * @param  string[] $availableLanguages  Список доступных локалей.
     * @return array<string, mixed>|null
     */
    private function buildCipherPayload(int $cipherId, array $availableLanguages): ?array
    {
        $cipher = $this->ciphers->find($cipherId);

        if ($cipher === null) {
            return null;
        }

        $cipherTranslations = $this->groupByLanguage(
            $this->ciphers->listCipherTranslationsByCipherId($cipherId),
            'language'
        );

        $blocks = $this->ciphers->listBlocksByCipherId($cipherId);
        $blockIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $blocks);
        $blockTranslations = $this->groupByEntityAndLanguage(
            $this->ciphers->listBlockTranslationsByBlockIds($blockIds),
            'block_id',
            'language'
        );

        $faqItems = $this->ciphers->listFaqByCipherId($cipherId);
        $faqIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $faqItems);
        $faqTranslations = $this->groupByEntityAndLanguage(
            $this->ciphers->listFaqTranslationsByFaqIds($faqIds),
            'faq_id',
            'language'
        );

        $examples = $this->ciphers->listExamplesByCipherId($cipherId);
        $exampleIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $examples);
        $exampleTranslations = $this->groupByEntityAndLanguage(
            $this->ciphers->listExampleTranslationsByExampleIds($exampleIds),
            'example_id',
            'language'
        );

        return [
            'cipher' => $cipher,
            'translations_by_language' => $cipherTranslations,
            'blocks' => $this->attachTranslations($blocks, $blockTranslations),
            'faq' => $this->attachTranslations($faqItems, $faqTranslations),
            'examples' => $this->attachTranslations($examples, $exampleTranslations),
            'available_languages' => $availableLanguages,
        ];
    }

    /**
     * Возвращает доступные языки интерфейса.
     *
     * @return string[]
     */
    private function availableLanguages(): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $language): string => mb_strtolower(trim((string) $language)),
            (array) config('locale.locales', [])
        ), static fn (string $language): bool => $language !== ''));
    }

    /**
     * Группирует строки по языку.
     *
     * @param  array<int, array<string, mixed>> $rows
     * @return array<string, array<string, mixed>>
     */
    private function groupByLanguage(array $rows, string $languageKey): array
    {
        $result = [];

        foreach ($rows as $row) {
            $language = mb_strtolower((string) ($row[$languageKey] ?? ''));

            if ($language !== '') {
                $result[$language] = $row;
            }
        }

        return $result;
    }

    /**
     * Группирует строки переводов по сущности и языку.
     *
     * @param  array<int, array<string, mixed>> $rows
     * @return array<int, array<string, array<string, mixed>>>
     */
    private function groupByEntityAndLanguage(array $rows, string $idKey, string $languageKey): array
    {
        $result = [];

        foreach ($rows as $row) {
            $entityId = (int) ($row[$idKey] ?? 0);
            $language = mb_strtolower((string) ($row[$languageKey] ?? ''));

            if ($entityId > 0 && $language !== '') {
                $result[$entityId][$language] = $row;
            }
        }

        return $result;
    }

    /**
     * Добавляет к строкам сущностей карту переводов.
     *
     * @param  array<int, array<string, mixed>>                $rows
     * @param  array<int, array<string, array<string, mixed>>> $translationsMap
     * @return array<int, array<string, mixed>>
     */
    private function attachTranslations(array $rows, array $translationsMap): array
    {
        $result = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $row['translations_by_language'] = $translationsMap[$id] ?? [];
            $result[] = $row;
        }

        return $result;
    }
}
