<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcceptInvitationAction
{
    public function execute(Invitation $invitation, User $user): void
    {
        /** @var InvitationStatus $status */
        $status = $invitation->status;

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => match ($status) {
                    InvitationStatus::Accepted => 'This invitation has already been accepted.',
                    InvitationStatus::Revoked  => 'This invitation has been revoked.',
                    default                    => 'This invitation has expired.',
                },
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'token' => 'This invitation has expired. Please request a new one.',
            ]);
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw ValidationException::withMessages([
                'email' => 'This invitation was sent to a different email address.',
            ]);
        }

        DB::transaction(function () use ($invitation, $user): void {
            $alreadyMember = $invitation->workspace->users()
                ->where('users.id', $user->id)
                ->exists();

            if (! $alreadyMember) {
                /** @var Role $role */
                $role = $invitation->role;

                $invitation->workspace->users()->attach($user->id, [
                    'role'      => $role->value,
                    'status'    => 'active',
                    'joined_at' => now(),
                ]);
            }

            $invitation->update([
                'status'      => InvitationStatus::Accepted->value,
                'accepted_at' => now(),
            ]);
        });
    }
}
