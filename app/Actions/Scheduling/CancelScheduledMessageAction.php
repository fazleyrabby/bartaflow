<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Enums\ScheduleStatus;
use App\Models\ScheduledMessage;

final class CancelScheduledMessageAction
{
    /**
     * Cancel a pending schedule. Returns false if it's already past the
     * pending state (processing/sent/canceled/failed) and cannot be canceled.
     */
    public function execute(ScheduledMessage $schedule): bool
    {
        if ($schedule->status !== ScheduleStatus::Pending) {
            return false;
        }

        $schedule->update(['status' => ScheduleStatus::Canceled]);

        return true;
    }
}
