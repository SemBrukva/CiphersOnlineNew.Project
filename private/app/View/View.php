<?php

declare(strict_types=1);

namespace App\View;

use Smarty\Smarty;

/**
 * Обёртка над шаблонизатором Smarty.
 *
 * Предоставляет методы рендеринга шаблонов, флюент-сеттеры для метаданных
 * страницы и глобальной передачи переменных во все шаблоны через share().
 */
final class View
{
    private Smarty $smarty;

    /**
     * Создаёт экземпляр View и конфигурирует Smarty.
     *
     * @param array<string, mixed> $config Конфигурация из config/view.php.
     */
    public function __construct(array $config)
    {
        $smarty = new Smarty();

        $smarty->setTemplateDir($config['views_path']);
        $smarty->setCompileDir($config['compile_path']);
        $smarty->setCacheDir($config['cache_path']);

        $smarty->debugging = $config['debug'];
        $smarty->caching = $config['caching'];
        $smarty->force_compile = $config['force_compile'];
        $smarty->escape_html = $config['escape_html'];

        $smarty->registerPlugin('function', 'vite', static function (array $params, \Smarty\Template $template): string {
            $nonce = $template->getTemplateVars('csp_nonce');
            return ViteAssets::tags($params['entry'] ?? '', $params['type'] ?? 'all', is_string($nonce) ? $nonce : null);
        });

        $smarty->registerPlugin('function', 'trans', static function (array $params): string {
            $key = isset($params['key']) && is_string($params['key']) ? $params['key'] : '';

            if ($key === '') {
                return '';
            }

            unset($params['key']);

            return trans($key, $params);
        });

        $smarty->registerPlugin('function', 'trans_choice', static function (array $params): string {
            $key = isset($params['key']) && is_string($params['key']) ? $params['key'] : '';
            $count = isset($params['count']) ? (int) $params['count'] : 0;

            if ($key === '') {
                return '';
            }

            unset($params['key'], $params['count']);

            return trans_choice($key, $count, $params);
        });

        $smarty->registerPlugin('modifier', 'starts_with', static fn (string $str, string $prefix): bool => str_starts_with($str, $prefix));
        $smarty->registerPlugin('modifier', 'json_encode', static fn (mixed $val, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string => json_encode($val, $flags) ?: '');

        $this->smarty = $smarty;
    }

    /**
     * Глобально назначает переменную во все последующие шаблоны.
     *
     * @param string $key   Имя переменной в шаблоне.
     * @param mixed  $value Значение переменной.
     */
    public function share(string $key, mixed $value): void
    {
        $this->smarty->assign($key, $value);
    }

    /**
     * Рендерит шаблон с переданными данными и возвращает HTML-строку.
     * Используется для partial-шаблонов и admin-шаблонов с наследованием.
     *
     * @param string               $template Путь к шаблону относительно views_path.
     * @param array<string, mixed> $data     Переменные для шаблона.
     */
    public function fetch(string $template, array $data = []): string
    {
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        return $this->smarty->fetch($template);
    }

    /**
     * Рендерит лейаут с ранее установленными данными страницы.
     *
     * @param string $layout Путь к лейауту относительно views_path.
     */
    public function render(string $layout = 'layouts/main.tpl'): string
    {
        return $this->smarty->fetch($layout);
    }

    /**
     * Устанавливает заголовок вкладки браузера (тег <title>).
     */
    public function setTitle(string $title): static
    {
        $this->smarty->assign('title', $title);
        return $this;
    }

    /**
     * Устанавливает основной заголовок страницы (тег h1).
     */
    public function setH1(string $h1): static
    {
        $this->smarty->assign('h1', $h1);
        return $this;
    }

    /**
     * Устанавливает мета-описание страницы.
     */
    public function setMeta(string $description): static
    {
        $this->smarty->assign('meta_description', $description);
        return $this;
    }

    /**
     * Устанавливает HTML-содержимое основной области страницы.
     */
    public function setContent(string $html): static
    {
        $this->smarty->assign('content', $html);
        return $this;
    }

    /**
     * Устанавливает HTML-содержимое боковой панели.
     * Если не вызван — сайдбар не отображается, контент занимает всю ширину.
     */
    public function setSidebar(string $html): static
    {
        $this->smarty->assign('sidebar', $html);
        return $this;
    }

    /**
     * Устанавливает хлебные крошки страницы.
     *
     * @param array<array{label: string, url?: string}> $crumbs
     */
    public function setBreadcrumbs(array $crumbs): static
    {
        $this->smarty->assign('breadcrumbs', $crumbs);
        return $this;
    }

    /**
     * Устанавливает значение мета-тега robots (например, «noindex,follow»).
     */
    public function setRobots(string $value): static
    {
        $this->smarty->assign('meta_robots', $value);
        return $this;
    }
}
