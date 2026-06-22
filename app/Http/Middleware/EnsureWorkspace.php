<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Tenancy\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWorkspace
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $workspaceId = $request->session()->get('workspace_id');

        // Try session-stored workspace first; verify user is an active member.
        $workspace = null;

        if ($workspaceId) {
            $workspace = $user->workspaces()
                ->where('workspaces.id', $workspaceId)
                ->where('workspace_users.status', 'active')
                ->first();
        }

        // Fall back to the first active workspace the user belongs to.
        if (! $workspace) {
            $workspace = $user->workspaces()
                ->where('workspace_users.status', 'active')
                ->first();
        }

        if (! $workspace) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your workspace access has been revoked. Please contact the workspace owner.');
        }

        $request->session()->put('workspace_id', $workspace->id);
        $this->current->set($workspace);

        return $next($request);
    }
}
