<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InviteMemberAction
{
    public function execute(Workspace $workspace, User $invitedBy, string $email, Role $role): Invitation
    {
        // Owner role cannot be granted via invitation (only ownership transfer).
        if ($role === Role::Owner) {
            throw new \InvalidArgumentException('Owner role cannot be assigned via invitation.');
        }

        // Check if already a member.
        $alreadyMember = $workspace->users()->where('users.email', $email)->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => 'This person is already a member of the workspace.',
            ]);
        }

        // Revoke any existing pending invitation for this email.
        Invitation::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('email', $email)
            ->where('status', InvitationStatus::Pending->value)
            ->update(['status' => InvitationStatus::Revoked->value]);

        $invitation = Invitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $invitedBy->id,
            'email' => $email,
            'role' => $role->value,
            'token' => Str::random(64),
            'status' => InvitationStatus::Pending->value,
            'expires_at' => now()->addDays(7),
        ]);

        // Send the invitation email.
        Notification::route('mail', $email)
            ->notify(new InvitationNotification($invitation, $invitedBy, $workspace));

        return $invitation;
    }
}
