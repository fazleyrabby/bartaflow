<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Contacts\CreateContactAction;
use App\Actions\Contacts\DeleteContactAction;
use App\Actions\Contacts\ImportContactsAction;
use App\Actions\Contacts\ToggleOptOutAction;
use App\Actions\Contacts\UpdateContactAction;
use App\Http\Requests\Contacts\ImportContactsRequest;
use App\Http\Requests\Contacts\StoreContactRequest;
use App\Http\Requests\Contacts\UpdateContactRequest;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(): View
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [Contact::class, $workspace->id]);

        $contacts = Contact::where('workspace_id', $workspace->id)
            ->with('tags')
            ->when(request('search'), fn ($q, $s) => $q->whereAny([
                'name', 'phone', 'email',
            ], 'like', '%'.$s.'%'))
            ->when(request('tag'), fn ($q, $t) => $q->whereHas('tags', fn ($q) => $q->where('contact_tags.id', $t)))
            ->when(request('opted_out') === '1', fn ($q) => $q->where('is_opted_out', true))
            ->when(request('opted_out') === '0', fn ($q) => $q->where('is_opted_out', false))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $tags = ContactTag::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get();

        return view('contacts.index', compact('contacts', 'tags', 'workspace'));
    }

    public function store(StoreContactRequest $request, CreateContactAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('create', [Contact::class, $workspace->id]);

        $data = $request->validatedWithNormalizedPhone();
        $tagIds = $data['tags'] ?? null;
        unset($data['tags']);

        $action->execute($workspace, $data, $tagIds);

        return redirect()->route('contacts.index')
            ->with('status', 'Contact created successfully.');
    }

    public function update(UpdateContactRequest $request, Contact $contact, UpdateContactAction $action): RedirectResponse
    {
        $this->authorize('update', $contact);
        $this->ensureBelongsToCurrentWorkspace($contact);

        $data = $request->validatedWithNormalizedPhone();
        $tagIds = $data['tags'] ?? null;
        unset($data['tags']);

        $action->execute($contact, $data, $tagIds);

        return redirect()->route('contacts.index')
            ->with('status', 'Contact updated successfully.');
    }

    public function destroy(Contact $contact, DeleteContactAction $action): RedirectResponse
    {
        $this->authorize('delete', $contact);
        $this->ensureBelongsToCurrentWorkspace($contact);

        $action->execute($contact);

        return redirect()->route('contacts.index')
            ->with('status', 'Contact deleted successfully.');
    }

    public function toggleOptOut(Contact $contact, ToggleOptOutAction $action): RedirectResponse
    {
        $this->authorize('update', $contact);
        $this->ensureBelongsToCurrentWorkspace($contact);

        $action->execute($contact);

        return redirect()->route('contacts.index')
            ->with('status', 'Contact opt-out status updated.');
    }

    public function import(ImportContactsRequest $request, ImportContactsAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('create', [Contact::class, $workspace->id]);

        $action->execute($workspace, $request->file('file'));

        return redirect()->route('contacts.index')
            ->with('status', 'Import started. Check back shortly for results.');
    }

    private function ensureBelongsToCurrentWorkspace(Contact $contact): void
    {
        if ($contact->workspace_id !== $this->current->id()) {
            abort(404);
        }
    }
}
