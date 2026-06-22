<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Support\Facades\DB;

final class TransferOwnershipAction
{
    public function execute(Workspace $workspace, User $currentOwner, User $newOwner): Workspace
    {
        return DB::transaction(function () use ($workspace, $currentOwner, $newOwner): Workspace {
            // Demote current owner → admin
            WorkspaceUser::where('workspace_id', $workspace->id)
                ->where('user_id', $currentOwner->id)
                ->update(['role' => Role::Admin->value]);

            // Promote new owner → owner
            WorkspaceUser::where('workspace_id', $workspace->id)
                ->where('user_id', $newOwner->id)
                ->update(['role' => Role::Owner->value]);

            // Update denormalized owner_id on workspace
            $workspace->update(['owner_id' => $newOwner->id]);

            return $workspace->fresh() ?? $workspace;
        });
    }
}
