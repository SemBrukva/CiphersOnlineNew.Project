<?php

declare(strict_types=1);

use App\Queue\Jobs\SendMailJob;

return [

    /*
     | Максимальное количество попыток выполнения задачи перед
     | переносом в failed_jobs. Учитывает все retries, включая первую попытку.
     */
    'max_attempts' => (int) env('QUEUE_MAX_ATTEMPTS', 3),

    /*
     | Сколько секунд ждать перед повторной попыткой выполнения задачи
     | (release back) и через какое время считать "застрявшую"
     | зарезервированную задачу заброшенной и доступной для повторного pop().
     */
    'retry_after' => (int) env('QUEUE_RETRY_AFTER', 90),

    /*
     | Вайтлист классов, разрешённых при десериализации payload.
     | Передаётся в unserialize(allowed_classes). Добавляйте сюда каждый
     | новый класс задачи — это защищает от object-injection при компрометации БД.
     */
    'job_classes' => [
        SendMailJob::class,
    ],

];
