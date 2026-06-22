<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Workspaces\InviteMemberAction;
use App\Actions\Workspaces\RemoveMemberAction;
use App\Actions\Workspaces\UpdateMemberRoleAction;
use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Http\Requests\Workspace\InviteMemberRequest;
use App\Http\Requests\Workspace\UpdateMemberRoleRequest;
use App\Models\Invitation;
use App\Models\WorkspaceUser;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(): View
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [WorkspaceUser::class, $workspace]);

        $members = WorkspaceUser::with('user')
            ->where('workspace_id', $workspace->id)
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END")
            ->get();

        $invitations = Invitation::withoutGlobalScopes()
            ->with('invitedBy')
            ->where('workspace_id', $workspace->id)
            ->where('status', InvitationStatus::Pending->value)
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        $assignableRoles = Role::assignable();

        return view('settings.team', compact('workspace', 'members', 'invitations', 'assignableRoles'));
    }

    public function invite(InviteMemberRequest $request, InviteMemberAction $action): RedirectResponse
    {
        $workspace = $this->current->get();

        $action->execute(
            $workspace,
            $request->user(),
            $request->string('email')->toString(),
            Role::from($request->string('role')->toString()),
        );

        return redirect()->route('settings.team')
            ->with('status', 'Invitation sent to '.$request->string('email').'.');
    }

    public function updateRole(
        UpdateMemberRoleRequest $request,
        WorkspaceUser $membership,
        UpdateMemberRoleAction $action,
    ): RedirectResponse {
        $workspace = $this->current->get();
        $this->authorize('updateRole', [$membership, $workspace]);

        // Ensure membership belongs to current workspace (IDOR guard).
        if ($membership->workspace_id !== $workspace->id) {
            abort(404);
        }

        $action->execute($membership, Role::from($request->string('role')->toString()));

        return redirect()->route('settings.team')
            ->with('status', 'Role updated successfully.');
    }

    public function remove(WorkspaceUser $membership, RemoveMemberAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('remove', [$membership, $workspace]);

        if ($membership->workspace_id !== $workspace->id) {
            abort(404);
        }

        $action->execute($workspace, $membership);

        return redirect()->route('settings.team')
            ->with('status', 'Member removed from the workspace.');
    }
}
