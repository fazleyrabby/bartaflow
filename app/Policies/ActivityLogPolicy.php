<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\WorkspaceUser;
use App\Services\Tenancy\CurrentWorkspace;

class ActivityLogPolicy
{
    public function viewAny(User $user, ?int $workspaceId = null): bool
    {
        $wsId = $workspaceId ?? app(CurrentWorkspace::class)->id();

        $membership = WorkspaceUser::where('workspace_id', $wsId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($membership === null) {
            return false;
        }

        /** @var Role $role */
        $role = $membership->role;

        return $role->isAtLeast(Role::Admin);
    }
}
