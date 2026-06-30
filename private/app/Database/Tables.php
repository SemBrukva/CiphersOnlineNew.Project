<?php

declare(strict_types=1);

namespace App\Database;

/**
 * Реестр названий таблиц базы данных.
 *
 * Все обращения к таблицам в коде должны использовать эти константы,
 * чтобы переименование таблицы требовало правки только в одном месте.
 */
final class Tables
{
    /** @var string Таблица пользователей. */
    public const string USERS = 'users';

    /** @var string Таблица системных страниц (Privacy Policy и др.). */
    public const string SYSTEM_PAGES = 'system_pages';

    /** @var string Таблица HTTP-редиректов. */
    public const string REDIRECTS = 'redirects';

    /** @var string Таблица обращений с формы контактов. */
    public const string CONTACTS = 'contacts';

    /** @var string Таблица задач очереди, ожидающих исполнения. */
    public const string JOBS = 'jobs';

    /** @var string Таблица задач очереди, исчерпавших попытки выполнения. */
    public const string FAILED_JOBS = 'failed_jobs';

    /** @var string Таблица категорий шифров. */
    public const string CIPHER_CATEGORIES = 'ciphers_categories';

    /** @var string Таблица переводов категорий шифров. */
    public const string CIPHER_CATEGORY_TRANSLATIONS = 'ciphers_categories_translations';

    /** @var string Таблица блоков контента категорий шифров. */
    public const string CIPHERS_CATEGORIES_BLOCKS = 'ciphers_categories_blocks';

    /** @var string Таблица переводов блоков контента категорий шифров. */
    public const string CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS = 'ciphers_categories_blocks_translations';

    /** @var string Таблица задач категорий шифров. */
    public const string CIPHERS_CATEGORIES_TASKS = 'ciphers_categories_tasks';

    /** @var string Таблица переводов задач категорий шифров. */
    public const string CIPHERS_CATEGORIES_TASKS_TRANSLATIONS = 'ciphers_categories_tasks_translations';

    /** @var string Таблица связок инструментов, которые часто используются вместе в категории. */
    public const string CIPHERS_CATEGORIES_USED_TOGETHER = 'ciphers_categories_used_together';

    /** @var string Таблица переводов связок инструментов, используемых вместе. */
    public const string CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS = 'ciphers_categories_used_together_translations';

    /** @var string Таблица FAQ категорий шифров. */
    public const string CIPHERS_CATEGORIES_FAQ = 'ciphers_categories_faq';

    /** @var string Таблица переводов FAQ категорий шифров. */
    public const string CIPHERS_CATEGORIES_FAQ_TRANSLATIONS = 'ciphers_categories_faq_translations';

    /** @var string Таблица приложений-шифров. */
    public const string CIPHERS = 'ciphers';

    /** @var string Таблица переводов приложений-шифров. */
    public const string CIPHERS_TRANSLATIONS = 'ciphers_translations';

    /** @var string Таблица блоков контента приложений-шифров. */
    public const string CIPHERS_BLOCKS = 'ciphers_blocks';

    /** @var string Таблица переводов блоков контента приложений-шифров. */
    public const string CIPHERS_BLOCKS_TRANSLATIONS = 'ciphers_blocks_translations';

    /** @var string Таблица примеров приложений-шифров. */
    public const string CIPHERS_EXAMPLES = 'ciphers_examples';

    /** @var string Таблица переводов примеров приложений-шифров. */
    public const string CIPHERS_EXAMPLES_TRANSLATIONS = 'ciphers_examples_translations';

    /** @var string Таблица FAQ приложений-шифров. */
    public const string CIPHERS_FAQ = 'ciphers_faq';

    /** @var string Таблица переводов FAQ приложений-шифров. */
    public const string CIPHERS_FAQ_TRANSLATIONS = 'ciphers_faq_translations';

    /** @var string Таблица тегов приложений-шифров. */
    public const string CIPHERS_TAGS = 'ciphers_tags';

    /** @var string Таблица переводов тегов приложений-шифров. */
    public const string CIPHERS_TAGS_TRANSLATIONS = 'ciphers_tags_translations';

    /** @var string Таблица событий использования инструментов (аналитика). */
    public const string TOOL_USAGE_EVENTS = 'tool_usage_events';

    /** @var string Таблица кластеров семантического ядра. */
    public const string SEMANTIC_CLUSTERS = 'semantic_clusters';

    /** @var string Таблица поисковых запросов семантического ядра. */
    public const string SEMANTIC_QUERIES = 'semantic_queries';

    /** @var string Таблица снимков поисковых позиций по запросам. */
    public const string SEMANTIC_RANK_SNAPSHOTS = 'semantic_rank_snapshots';

    /** @var string Таблица кеша индексации страниц инструментов в поисковых системах. */
    public const string TOOL_INDEXATION_SNAPSHOTS = 'tool_indexation_snapshots';
}
