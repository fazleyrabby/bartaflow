<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $this->getMembership($user, $workspace) !== null;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->hasAtLeast($user, $workspace, Role::Admin);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->hasAtLeast($user, $workspace, Role::Owner);
    }

    public function transferOwnership(User $user, Workspace $workspace): bool
    {
        return $this->hasAtLeast($user, $workspace, Role::Owner);
    }

    // -------------------------------------------------------------------------

    private function getMembership(User $user, Workspace $workspace): ?WorkspaceUser
    {
        return WorkspaceUser::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    private function hasAtLeast(User $user, Workspace $workspace, Role $minimum): bool
    {
        $membership = $this->getMembership($user, $workspace);

        if ($membership === null) {
            return false;
        }

        /** @var Role $role */
        $role = $membership->role;

        return $role->isAtLeast($minimum);
    }
}
