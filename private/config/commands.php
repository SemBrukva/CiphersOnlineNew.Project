<?php

declare(strict_types=1);

// Карта консольных команд: имя команды → FQCN класса, используется в bin/console.

use App\Console\Commands\CipherContentExportCommand;
use App\Console\Commands\CipherContentImportCommand;
use App\Console\Commands\ConfigCacheCommand;
use App\Console\Commands\ConfigClearCommand;
use App\Console\Commands\MailTestCommand;
use App\Console\Commands\Make\MakeControllerCommand;
use App\Console\Commands\Make\MakeJobCommand;
use App\Console\Commands\Make\MakeMiddlewareCommand;
use App\Console\Commands\Make\MakeMigrationCommand;
use App\Console\Commands\Make\MakeRepositoryCommand;
use App\Console\Commands\MigrateCommand;
use App\Console\Commands\MigrateRollbackCommand;
use App\Console\Commands\MigrateStatusCommand;
use App\Console\Commands\OpenApiCommand;
use App\Console\Commands\QueueRetryCommand;
use App\Console\Commands\QueueWorkCommand;
use App\Console\Commands\RouteCacheCommand;
use App\Console\Commands\RouteClearCommand;
use App\Console\Commands\RouteListCommand;

return [
    'migrate'           => MigrateCommand::class,
    'migrate:rollback'  => MigrateRollbackCommand::class,
    'migrate:status'    => MigrateStatusCommand::class,
    'mail:test'         => MailTestCommand::class,
    'config:cache'      => ConfigCacheCommand::class,
    'config:clear'      => ConfigClearCommand::class,
    'route:cache'       => RouteCacheCommand::class,
    'route:clear'       => RouteClearCommand::class,
    'route:list'        => RouteListCommand::class,
    'queue:work'        => QueueWorkCommand::class,
    'queue:retry'       => QueueRetryCommand::class,
    'make:controller'   => MakeControllerCommand::class,
    'make:middleware'   => MakeMiddlewareCommand::class,
    'make:migration'    => MakeMigrationCommand::class,
    'make:repository'   => MakeRepositoryCommand::class,
    'make:job'          => MakeJobCommand::class,
    'openapi:generate'  => OpenApiCommand::class,
    'cipher:content:export' => CipherContentExportCommand::class,
    'cipher:content:import' => CipherContentImportCommand::class,
];
