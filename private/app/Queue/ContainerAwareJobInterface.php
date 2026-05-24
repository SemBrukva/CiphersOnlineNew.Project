<?php

declare(strict_types=1);

namespace App\Queue;

use App\Container\Container;

/**
 * Опциональный интерфейс для задач, которым нужен доступ к контейнеру.
 *
 * Воркер автоматически вызовет setContainer() перед handle(), если задача
 * реализует этот интерфейс. Это позволяет получать зависимости вроде Mailer
 * без необходимости сериализовывать их в payload.
 */
interface ContainerAwareJobInterface extends JobInterface
{
    /**
     * Устанавливает контейнер, доступный во время handle().
     */
    public function setContainer(Container $container): void;
}
