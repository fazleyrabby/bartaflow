<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactTag;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactTagController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(): JsonResponse
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [ContactTag::class, $workspace->id]);

        $tags = ContactTag::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return response()->json($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $workspace = $this->current->get();
        $this->authorize('create', [ContactTag::class, $workspace->id]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $tag = ContactTag::create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'color' => $data['color'] ?? '#6b7280',
        ]);

        return response()->json($tag, 201);
    }

    public function update(Request $request, ContactTag $tag): JsonResponse
    {
        $this->authorize('update', $tag);
        $this->ensureBelongsToCurrentWorkspace($tag);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $tag->update($data);

        return response()->json($tag->fresh());
    }

    public function destroy(ContactTag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);
        $this->ensureBelongsToCurrentWorkspace($tag);

        $tag->delete();

        return redirect()->route('contacts.index')
            ->with('status', 'Tag deleted successfully.');
    }

    private function ensureBelongsToCurrentWorkspace(ContactTag $tag): void
    {
        if ($tag->workspace_id !== $this->current->id()) {
            abort(404);
        }
    }
}
