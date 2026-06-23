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
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(
        private readonly CurrentWorkspace $current,
        private readonly AuditLogger $audit,
    ) {}

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

        $email = $request->string('email')->toString();
        $role = Role::from($request->string('role')->toString());

        $action->execute($workspace, $request->user(), $email, $role);

        $this->audit->log('team.invited', $workspace, "Invited {$email} as {$role->value}", [
            'email' => $email,
            'role' => $role->value,
        ]);

        return redirect()->route('settings.team')
            ->with('status', 'Invitation sent to '.$email.'.');
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

        $newRole = Role::from($request->string('role')->toString());
        /** @var Role $oldRole */
        $oldRole = $membership->role;

        $action->execute($membership, $newRole);

        $this->audit->log('team.role_changed', $membership, "Changed role to {$newRole->value}", [
            'user_id' => $membership->user_id,
            'from' => $oldRole->value,
            'to' => $newRole->value,
        ]);

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

        $removedUserId = $membership->user_id;

        $action->execute($workspace, $membership);

        $this->audit->log('team.member_removed', $workspace, 'Removed a member from the workspace', [
            'user_id' => $removedUserId,
        ]);

        return redirect()->route('settings.team')
            ->with('status', 'Member removed from the workspace.');
    }
}
