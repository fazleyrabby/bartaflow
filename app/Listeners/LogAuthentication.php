<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Login;

class LogAuthentication
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        // Workspace context is not yet resolved at login time, so this is a
        // global (workspace_id null) audit entry attributed to the actor.
        $this->audit->log(
            'auth.login',
            $user instanceof User ? $user : null,
            'User signed in',
            workspaceId: null,
            userId: $user instanceof User ? (int) $user->id : null,
        );
    }
}
