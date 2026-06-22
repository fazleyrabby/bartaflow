<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Workspaces\AcceptInvitationAction;
use App\Actions\Workspaces\InviteMemberAction;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $invitation = Invitation::withoutGlobalScopes()
            ->with(['workspace', 'invitedBy'])
            ->where('token', $token)
            ->firstOrFail();

        if (! $invitation->isPending()) {
            return redirect()->route('login')
                ->with('error', 'This invitation is no longer valid.');
        }

        return view('invitations.show', compact('invitation'));
    }

    public function accept(Request $request, string $token, AcceptInvitationAction $action): RedirectResponse
    {
        $invitation = Invitation::withoutGlobalScopes()
            ->with('workspace')
            ->where('token', $token)
            ->firstOrFail();

        $action->execute($invitation, $request->user());

        // Switch the session to the newly joined workspace.
        $request->session()->put('workspace_id', $invitation->workspace_id);

        return redirect()->route('dashboard')
            ->with('status', 'You have joined '.$invitation->workspace->name.'!');
    }

    public function resend(Invitation $invitation, InviteMemberAction $action, CurrentWorkspace $current): RedirectResponse
    {
        $workspace = $current->get();
        $this->authorize('resendInvitation', [\App\Models\WorkspaceUser::class, $workspace]);

        if ($invitation->workspace_id !== $workspace->id) {
            abort(404);
        }

        // Re-invite using same email + role (revokes old, creates fresh token).
        $action->execute($workspace, request()->user(), $invitation->email, $invitation->role);

        return redirect()->route('settings.team')
            ->with('status', 'Invitation resent to '.$invitation->email.'.');
    }

    public function revoke(Invitation $invitation, CurrentWorkspace $current): RedirectResponse
    {
        $workspace = $current->get();
        $this->authorize('revokeInvitation', [\App\Models\WorkspaceUser::class, $workspace]);

        if ($invitation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $invitation->update(['status' => InvitationStatus::Revoked->value]);

        return redirect()->route('settings.team')
            ->with('status', 'Invitation revoked.');
    }
}
