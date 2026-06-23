<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Message;
use App\Services\Dashboard\DashboardMetrics;

class MessageObserver
{
    public function saved(Message $message): void
    {
        DashboardMetrics::forget($message->workspace_id);
    }

    public function deleted(Message $message): void
    {
        DashboardMetrics::forget($message->workspace_id);
    }
}
