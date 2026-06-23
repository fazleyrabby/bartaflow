<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Messaging\RetryMessageAction;
use App\Actions\Messaging\SendTemplatedMessageAction;
use App\Enums\AccountStatus;
use App\Enums\MessageStatus;
use App\Enums\TemplateStatus;
use App\Exceptions\MessageSendException;
use App\Http\Requests\Messaging\SendMessageRequest;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Message;
use App\Models\Template;
use App\Models\WhatsAppAccount;
use App\Services\Audit\AuditLogger;
use App\Services\Messaging\SendTemplatedMessageData;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function __construct(
        private readonly CurrentWorkspace $current,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [Message::class, $workspace->id]);

        $messages = Message::where('workspace_id', $workspace->id)
            ->with(['template:id,name', 'account:id,label'])
            ->status($request->string('status')->toString())
            ->dateBetween($request->string('date_from')->toString(), $request->string('date_to')->toString())
            ->forAccount($request->integer('account_id') ?: null)
            ->forTemplate($request->integer('template_id') ?: null)
            ->search($request->string('search')->toString())
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $accounts = WhatsAppAccount::where('workspace_id', $workspace->id)
            ->orderBy('label')
            ->get(['id', 'label']);

        $templates = Template::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $failedCount = Message::where('workspace_id', $workspace->id)
            ->where('status', MessageStatus::Failed->value)
            ->count();

        return view('messages.index', compact('messages', 'workspace', 'accounts', 'templates', 'failedCount'));
    }

    public function show(Message $message): View
    {
        $this->ensureBelongsToCurrentWorkspace($message);
        $this->authorize('view', $message);

        $message->load(['template:id,name', 'account:id,label,phone_number', 'contact:id,name,phone']);

        return view('messages.show', [
            'message' => $message,
            'workspace' => $this->current->get(),
        ]);
    }

    public function retry(Message $message, RetryMessageAction $action): RedirectResponse
    {
        $this->ensureBelongsToCurrentWorkspace($message);
        $this->authorize('retry', $message);

        $requeued = $action->retry($message);

        if ($requeued) {
            $this->audit->log('message.retried', $message, 'Retried a failed message');
        }

        return back()->with(
            $requeued ? 'status' : 'error',
            $requeued ? 'Message re-queued.' : 'Only failed messages can be retried.'
        );
    }

    public function bulkRetry(Request $request, RetryMessageAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [Message::class, $workspace->id]);

        $ids = array_map('intval', (array) $request->input('message_ids', []));
        $count = $action->retryMany($workspace->id, $ids);

        if ($count > 0) {
            $this->audit->log('message.retried', null, "Bulk-retried {$count} failed message(s)", ['count' => $count]);
        }

        return back()->with(
            $count > 0 ? 'status' : 'error',
            $count > 0
                ? "{$count} message".($count === 1 ? '' : 's').' re-queued.'
                : 'No failed messages selected.'
        );
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

        $this->audit->log('message.sent', null, "Queued {$result->queued} message(s)", [
            'queued' => $result->queued,
            'skipped' => $result->skipped,
            'template_id' => $data->templateId,
            'account_id' => $data->accountId,
        ]);

        $msg = "{$result->queued} message".($result->queued === 1 ? '' : 's').' queued';
        if ($result->skipped > 0) {
            $msg .= " ({$result->skipped} skipped)";
        }

        return redirect()->route('messages.index')->with('status', $msg.'.');
    }

    private function ensureBelongsToCurrentWorkspace(Message $message): void
    {
        if ($message->workspace_id !== $this->current->id()) {
            abort(404);
        }
    }
}
