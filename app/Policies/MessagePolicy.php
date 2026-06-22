<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use App\Services\Tenancy\CurrentWorkspace;

class MessagePolicy
{
    public function viewAny(User $user, ?int $workspaceId = null): bool
    {
        return $this->isMember($user, $workspaceId);
    }

    public function view(User $user, Message $message): bool
    {
        return $message->workspace_id === app(CurrentWorkspace::class)->id();
    }

    public function create(User $user, ?int $workspaceId = null): bool
    {
        return $this->isMember($user, $workspaceId);
    }

    public function retry(User $user, Message $message): bool
    {
        return $message->workspace_id === app(CurrentWorkspace::class)->id();
    }

    private function isMember(User $user, ?int $workspaceId): bool
    {
        $wsId = $workspaceId ?? app(CurrentWorkspace::class)->id();

        return $user->workspaces()->where('workspace_id', $wsId)->exists();
    }
}
