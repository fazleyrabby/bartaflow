<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\Role;
use App\Models\WorkspaceUser;
use Illuminate\Validation\ValidationException;

final class UpdateMemberRoleAction
{
    public function execute(WorkspaceUser $membership, Role $newRole): WorkspaceUser
    {
        if ($membership->role === Role::Owner) {
            throw ValidationException::withMessages([
                'role' => 'The Owner\'s role cannot be changed directly. Use ownership transfer instead.',
            ]);
        }

        if ($newRole === Role::Owner) {
            throw ValidationException::withMessages([
                'role' => 'You cannot assign the Owner role directly. Use ownership transfer.',
            ]);
        }

        $membership->update(['role' => $newRole->value]);

        return $membership->fresh() ?? $membership;
    }
}
