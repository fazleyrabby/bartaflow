<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\Workspace;

final class DeleteWorkspaceAction
{
    public function execute(Workspace $workspace): void
    {
        // Soft delete; child rows cascade only on hard delete.
        // Data is preserved and recoverable by a super-admin.
        $workspace->delete();
    }
}
