<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ScheduledMessage;
use App\Services\Dashboard\DashboardMetrics;

class ScheduledMessageObserver
{
    public function saved(ScheduledMessage $schedule): void
    {
        DashboardMetrics::forget($schedule->workspace_id);
    }

    public function deleted(ScheduledMessage $schedule): void
    {
        DashboardMetrics::forget($schedule->workspace_id);
    }
}
