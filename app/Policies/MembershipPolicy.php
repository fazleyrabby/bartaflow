<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;

class MembershipPolicy
{
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $this->getMembership($user, $workspace) !== null;
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        return $this->hasAtLeast($user, $workspace, Role::Admin);
    }

    public function updateRole(User $user, WorkspaceUser $target, Workspace $workspace): bool
    {
        if (! $this->hasAtLeast($user, $workspace, Role::Admin)) {
            return false;
        }

        // Cannot change the Owner's role; use ownership transfer for that.
        /** @var Role $role */
        $role = $target->role;

        return $role !== Role::Owner;
    }

    public function remove(User $user, WorkspaceUser $target, Workspace $workspace): bool
    {
        if (! $this->hasAtLeast($user, $workspace, Role::Admin)) {
            return false;
        }

        // Cannot remove the Owner.
        /** @var Role $role */
        $role = $target->role;

        return $role !== Role::Owner;
    }

    public function resendInvitation(User $user, Workspace $workspace): bool
    {
        return $this->hasAtLeast($user, $workspace, Role::Admin);
    }

    public function revokeInvitation(User $user, Workspace $workspace): bool
    {
        return $this->hasAtLeast($user, $workspace, Role::Admin);
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
