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
}
