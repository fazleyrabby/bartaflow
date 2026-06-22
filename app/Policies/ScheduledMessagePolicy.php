<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScheduledMessage;
use App\Models\User;
use App\Services\Tenancy\CurrentWorkspace;

class ScheduledMessagePolicy
{
    public function viewAny(User $user, ?int $workspaceId = null): bool
    {
        return $this->isMember($user, $workspaceId);
    }

    public function view(User $user, ScheduledMessage $schedule): bool
    {
        return $schedule->workspace_id === app(CurrentWorkspace::class)->id();
    }

    public function create(User $user, ?int $workspaceId = null): bool
    {
        return $this->isMember($user, $workspaceId);
    }

    public function update(User $user, ScheduledMessage $schedule): bool
    {
        return $schedule->workspace_id === app(CurrentWorkspace::class)->id();
    }

    public function cancel(User $user, ScheduledMessage $schedule): bool
    {
        return $schedule->workspace_id === app(CurrentWorkspace::class)->id();
    }

    private function isMember(User $user, ?int $workspaceId): bool
    {
        $wsId = $workspaceId ?? app(CurrentWorkspace::class)->id();

        return $user->workspaces()->where('workspace_id', $wsId)->exists();
    }
}
