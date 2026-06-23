<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Workspaces\DeleteWorkspaceAction;
use App\Actions\Workspaces\TransferOwnershipAction;
use App\Actions\Workspaces\UpdateWorkspaceAction;
use App\Http\Requests\Workspace\TransferOwnershipRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Models\User;
use App\Models\WorkspaceUser;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly CurrentWorkspace $current,
        private readonly AuditLogger $audit,
    ) {}

    public function edit(): View
    {
        $workspace = $this->current->get();
        $this->authorize('update', $workspace);

        $timezones = \DateTimeZone::listIdentifiers();

        return view('settings.workspace', compact('workspace', 'timezones'));
    }

    public function update(UpdateWorkspaceRequest $request, UpdateWorkspaceAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $action->execute($workspace, $request->validated());

        $this->audit->log('workspace.updated', $workspace, 'Updated workspace settings', [
            'changed' => array_keys($request->validated()),
        ]);

        return redirect()->route('settings.workspace')
            ->with('status', 'Workspace settings updated.');
    }

    public function transfer(TransferOwnershipRequest $request, TransferOwnershipAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('transferOwnership', $workspace);

        $newOwner = User::findOrFail($request->integer('user_id'));

        // Ensure new owner is an active member of this workspace.
        $isMember = WorkspaceUser::where('workspace_id', $workspace->id)
            ->where('user_id', $newOwner->id)
            ->where('status', 'active')
            ->exists();

        if (! $isMember) {
            return back()->with('error', 'The selected user is not a member of this workspace.');
        }

        $action->execute($workspace, $request->user(), $newOwner);

        $this->audit->log('workspace.ownership_transferred', $workspace, 'Transferred workspace ownership', [
            'to_user_id' => $newOwner->id,
        ]);

        return redirect()->route('settings.workspace')
            ->with('status', 'Ownership transferred to '.$newOwner->name.'.');
    }

    public function destroy(Request $request, DeleteWorkspaceAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('delete', $workspace);

        // Require typed confirmation.
        if ($request->string('confirm_name')->toString() !== $workspace->name) {
            return back()->with('error', 'Workspace name did not match. Deletion cancelled.');
        }

        $action->execute($workspace);

        // Clear the workspace from session and redirect to home.
        $request->session()->forget('workspace_id');

        return redirect()->route('home')
            ->with('status', 'Workspace deleted.');
    }
}
