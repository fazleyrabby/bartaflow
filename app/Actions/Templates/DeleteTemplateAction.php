<?php

declare(strict_types=1);

namespace App\Actions\Templates;

use App\Models\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DeleteTemplateAction
{
    /**
     * Soft-delete a template, unless a pending scheduled message still references it.
     *
     * @return bool true when deleted, false when blocked by a pending schedule
     */
    public function execute(Template $template): bool
    {
        if ($this->hasPendingSchedule($template)) {
            return false;
        }

        $template->delete();

        return true;
    }

    private function hasPendingSchedule(Template $template): bool
    {
        // The scheduling feature (task 008) is not built yet; guard defensively so
        // this protection activates automatically once the table exists.
        if (! Schema::hasTable('scheduled_messages')) {
            return false;
        }

        return DB::table('scheduled_messages')
            ->where('template_id', $template->id)
            ->whereIn('status', ['pending', 'scheduled', 'queued'])
            ->exists();
    }
}
