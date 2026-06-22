<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\WorkspaceUser;
use App\Services\Tenancy\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user      = $request->user();
        $workspace = app(CurrentWorkspace::class)->get();

        $membership = WorkspaceUser::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $membership) {
            abort(403);
        }

        // Allow if user's role is in the accepted list (caller specifies all allowed roles).
        $allowed = array_map(fn (string $r) => Role::from($r), $roles);

        if (in_array($membership->role, $allowed, true)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
