<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WorkspaceUser;

class WhatsAppAccountPolicy
{
    public function viewAny(User $user, int $workspaceId): bool
    {
        return $this->getMembership($user, $workspaceId) !== null;
    }

    public function view(User $user, WhatsAppAccount $account): bool
    {
        return $this->getMembership($user, $account->workspace_id) !== null;
    }

    public function create(User $user, int $workspaceId): bool
    {
        return $this->hasAtLeast($user, $workspaceId, Role::Admin);
    }

    public function update(User $user, WhatsAppAccount $account): bool
    {
        return $this->hasAtLeast($user, $account->workspace_id, Role::Admin);
    }

    public function delete(User $user, WhatsAppAccount $account): bool
    {
        return $this->hasAtLeast($user, $account->workspace_id, Role::Admin);
    }

    public function sendTest(User $user, WhatsAppAccount $account): bool
    {
        return $this->hasAtLeast($user, $account->workspace_id, Role::Admin);
    }

    public function setDefault(User $user, WhatsAppAccount $account): bool
    {
        return $this->hasAtLeast($user, $account->workspace_id, Role::Admin);
    }

    // -------------------------------------------------------------------------

    private function getMembership(User $user, int $workspaceId): ?WorkspaceUser
    {
        return WorkspaceUser::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    private function hasAtLeast(User $user, int $workspaceId, Role $minimum): bool
    {
        $membership = $this->getMembership($user, $workspaceId);

        if ($membership === null) {
            return false;
        }

        /** @var Role $role */
        $role = $membership->role;

        return $role->isAtLeast($minimum);
    }
}
