<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\Role;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Validation\ValidationException;

final class RemoveMemberAction
{
    public function execute(Workspace $workspace, WorkspaceUser $membership): void
    {
        if ($membership->role === Role::Owner) {
            throw ValidationException::withMessages([
                'member' => 'The Owner cannot be removed. Transfer ownership first.',
            ]);
        }

        $membership->delete();
    }
}
