<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Templates\CreateTemplateAction;
use App\Actions\Templates\DeleteTemplateAction;
use App\Actions\Templates\DuplicateTemplateAction;
use App\Actions\Templates\UpdateTemplateAction;
use App\Enums\TemplateCategory;
use App\Enums\TemplateStatus;
use App\Http\Requests\Templates\StoreTemplateRequest;
use App\Http\Requests\Templates\UpdateTemplateRequest;
use App\Models\Contact;
use App\Models\Template;
use App\Services\Templates\TemplateRenderer;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(): View
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [Template::class, $workspace->id]);

        $templates = Template::where('workspace_id', $workspace->id)
            ->when(request('search'), fn ($q, $s) => $q->where('name', 'like', '%'.$s.'%'))
            ->when(request('category'), fn ($q, $c) => $q->where('category', $c))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('templates.index', [
            'templates' => $templates,
            'categories' => TemplateCategory::cases(),
            'workspace' => $workspace,
        ]);
    }

    public function create(): View
    {
        $workspace = $this->current->get();
        $this->authorize('create', [Template::class, $workspace->id]);

        return view('templates.create', [
            'workspace' => $workspace,
            'categories' => TemplateCategory::cases(),
            'statuses' => TemplateStatus::cases(),
            'contacts' => $this->previewContacts($workspace->id),
        ]);
    }

    public function store(StoreTemplateRequest $request, CreateTemplateAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('create', [Template::class, $workspace->id]);

        $action->execute($workspace, $request->validated(), $request->user()?->id);

        return redirect()->route('templates.index')
            ->with('status', 'Template created successfully.');
    }

    public function edit(Template $template): View
    {
        $this->ensureBelongsToCurrentWorkspace($template);
        $this->authorize('update', $template);

        return view('templates.edit', [
            'template' => $template,
            'categories' => TemplateCategory::cases(),
            'statuses' => TemplateStatus::cases(),
            'contacts' => $this->previewContacts($template->workspace_id),
        ]);
    }

    public function update(UpdateTemplateRequest $request, Template $template, UpdateTemplateAction $action): RedirectResponse
    {
        $this->ensureBelongsToCurrentWorkspace($template);
        $this->authorize('update', $template);

        $action->execute($template, $request->validated());

        return redirect()->route('templates.index')
            ->with('status', 'Template updated successfully.');
    }

    public function duplicate(Template $template, DuplicateTemplateAction $action): RedirectResponse
    {
        $this->ensureBelongsToCurrentWorkspace($template);
        $this->authorize('create', [Template::class, $template->workspace_id]);

        $copy = $action->execute($template, request()->user()?->id);

        return redirect()->route('templates.edit', $copy)
            ->with('status', 'Template duplicated. Edit your copy below.');
    }

    public function destroy(Template $template, DeleteTemplateAction $action): RedirectResponse
    {
        $this->ensureBelongsToCurrentWorkspace($template);
        $this->authorize('delete', $template);

        $deleted = $action->execute($template);

        if (! $deleted) {
            return redirect()->route('templates.index')
                ->with('error', 'This template cannot be deleted while a scheduled message still uses it.');
        }

        return redirect()->route('templates.index')
            ->with('status', 'Template deleted successfully.');
    }

    /**
     * Live preview: render a body against an optional real contact, reporting missing variables.
     */
    public function preview(Request $request, TemplateRenderer $renderer): JsonResponse
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [Template::class, $workspace->id]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4096'],
            'contact_id' => ['nullable', 'integer'],
        ]);

        $contact = null;
        if (! empty($validated['contact_id'])) {
            $contact = Contact::where('workspace_id', $workspace->id)
                ->find($validated['contact_id']);
        }

        $result = $renderer->render($validated['body'], $contact, $workspace);

        return response()->json([
            'text' => $result->text,
            'missing' => $result->missing,
            'variables' => $renderer->parse($validated['body']),
        ]);
    }

    /**
     * @return Collection<int, Contact>
     */
    private function previewContacts(int $workspaceId)
    {
        return Contact::where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'phone', 'email']);
    }

    private function ensureBelongsToCurrentWorkspace(Template $template): void
    {
        if ($template->workspace_id !== $this->current->id()) {
            abort(404);
        }
    }
}
