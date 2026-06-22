<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Scheduling\DispatchDueSchedulesAction;
use Illuminate\Console\Command;

class DispatchDueSchedulesCommand extends Command
{
    protected $signature = 'schedule:dispatch-due';

    protected $description = 'Dispatch all due, pending scheduled messages through the send pipeline.';

    public function handle(DispatchDueSchedulesAction $action): int
    {
        $result = $action->execute();

        $this->info(sprintf(
            'Schedules processed — dispatched: %d, failed: %d, skipped: %d',
            $result['dispatched'],
            $result['failed'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
