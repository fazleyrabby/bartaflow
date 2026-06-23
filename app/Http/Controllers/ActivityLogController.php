<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(Request $request): View
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [ActivityLog::class, $workspace->id]);

        $logs = ActivityLog::where('workspace_id', $workspace->id)
            ->with('user:id,name,email')
            ->when($request->string('action')->toString(), fn ($q, $a) => $q->where('action', $a))
            ->when($request->integer('user_id') ?: null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->string('date_from')->toString(), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->string('date_to')->toString(), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('created_at')
            ->paginate(40)
            ->withQueryString();

        // Distinct action + actor lists for the filter dropdowns.
        $actions = ActivityLog::where('workspace_id', $workspace->id)
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $members = $workspace->users()->orderBy('name')->get(['users.id', 'name']);

        return view('settings.activity', compact('logs', 'workspace', 'actions', 'members'));
    }
}
