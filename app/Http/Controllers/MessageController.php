<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Messaging\SendTemplatedMessageAction;
use App\Enums\AccountStatus;
use App\Enums\TemplateStatus;
use App\Exceptions\MessageSendException;
use App\Http\Requests\Messaging\SendMessageRequest;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Message;
use App\Models\Template;
use App\Models\WhatsAppAccount;
use App\Services\Messaging\SendTemplatedMessageData;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(): View
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [Message::class, $workspace->id]);

        $messages = Message::where('workspace_id', $workspace->id)
            ->with(['template:id,name', 'account:id,label'])
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('messages.index', compact('messages', 'workspace'));
    }

    public function create(): View
    {
        $workspace = $this->current->get();
        $this->authorize('create', [Message::class, $workspace->id]);

        $accounts = WhatsAppAccount::where('workspace_id', $workspace->id)
            ->where('status', AccountStatus::Connected->value)
            ->orderByDesc('is_default')
            ->get(['id', 'label', 'phone_number', 'is_default']);

        $templates = Template::where('workspace_id', $workspace->id)
            ->where('status', TemplateStatus::Active->value)
            ->orderBy('name')
            ->get(['id', 'name', 'body', 'variables']);

        $tags = ContactTag::where('workspace_id', $workspace->id)
            ->withCount('contacts')
            ->orderBy('name')
            ->get(['id', 'name']);

        $contacts = Contact::where('workspace_id', $workspace->id)
            ->where('is_opted_out', false)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'phone']);

        $contactCount = Contact::where('workspace_id', $workspace->id)
            ->where('is_opted_out', false)
            ->count();

        return view('messages.create', compact(
            'workspace', 'accounts', 'templates', 'tags', 'contacts', 'contactCount'
        ));
    }

    public function store(SendMessageRequest $request, SendTemplatedMessageAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('create', [Message::class, $workspace->id]);

        $data = SendTemplatedMessageData::fromArray($request->validated());

        try {
            $result = $action->execute($workspace, $request->user(), $data);
        } catch (MessageSendException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $msg = "{$result->queued} message".($result->queued === 1 ? '' : 's').' queued';
        if ($result->skipped > 0) {
            $msg .= " ({$result->skipped} skipped)";
        }

        return redirect()->route('messages.index')->with('status', $msg.'.');
    }
}
