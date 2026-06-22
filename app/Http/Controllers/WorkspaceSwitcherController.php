<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceSwitcherController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate(['workspace_id' => ['required', 'integer']]);

        $workspaceId = $request->integer('workspace_id');

        $belongs = $request->user()
            ->workspaces()
            ->where('workspaces.id', $workspaceId)
            ->where('workspace_users.status', 'active')
            ->exists();

        if (! $belongs) {
            abort(403, 'You do not belong to that workspace.');
        }

        $request->session()->put('workspace_id', $workspaceId);

        return redirect()->route('dashboard');
    }
}
