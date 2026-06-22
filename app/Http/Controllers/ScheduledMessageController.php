<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Scheduling\CancelScheduledMessageAction;
use App\Actions\Scheduling\CreateScheduledMessageAction;
use App\Actions\Scheduling\UpdateScheduledMessageAction;
use App\Enums\AccountStatus;
use App\Enums\ScheduleStatus;
use App\Enums\TemplateStatus;
use App\Http\Requests\Scheduling\SaveScheduledMessageRequest;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\ScheduledMessage;
use App\Models\Template;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduledMessageController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(): View
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [ScheduledMessage::class, $workspace->id]);

        $schedules = ScheduledMessage::where('workspace_id', $workspace->id)
            ->with(['template:id,name', 'account:id,label'])
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('run_at')
            ->paginate(20)
            ->withQueryString();

        return view('scheduling.index', compact('schedules', 'workspace'));
    }

    public function create(): View
    {
        $workspace = $this->current->get();
        $this->authorize('create', [ScheduledMessage::class, $workspace->id]);

        return view('scheduling.create', $this->formData($workspace));
    }

    public function store(SaveScheduledMessageRequest $request, CreateScheduledMessageAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $this->authorize('create', [ScheduledMessage::class, $workspace->id]);

        $action->execute($workspace, (int) $request->user()->id, $this->payload($request));

        return redirect()->route('scheduling.index')
            ->with('status', 'Message scheduled successfully.');
    }

    public function edit(ScheduledMessage $scheduling): View
    {
        $this->ensureBelongsToCurrentWorkspace($scheduling);
        $this->authorize('update', $scheduling);

        return view('scheduling.edit', [
            'schedule' => $scheduling,
            ...$this->formData($this->current->get()),
        ]);
    }

    public function update(SaveScheduledMessageRequest $request, ScheduledMessage $scheduling, UpdateScheduledMessageAction $action): RedirectResponse
    {
        $this->ensureBelongsToCurrentWorkspace($scheduling);
        $this->authorize('update', $scheduling);

        if ($scheduling->status !== ScheduleStatus::Pending) {
            return redirect()->route('scheduling.index')
                ->with('error', 'Only pending schedules can be edited.');
        }

        $action->execute($scheduling, $this->payload($request));

        return redirect()->route('scheduling.index')
            ->with('status', 'Schedule updated successfully.');
    }

    public function cancel(ScheduledMessage $scheduling, CancelScheduledMessageAction $action): RedirectResponse
    {
        $this->ensureBelongsToCurrentWorkspace($scheduling);
        $this->authorize('cancel', $scheduling);

        $canceled = $action->execute($scheduling);

        return redirect()->route('scheduling.index')
            ->with($canceled ? 'status' : 'error', $canceled
                ? 'Schedule canceled.'
                : 'This schedule can no longer be canceled.');
    }

    /**
     * @return array{run_at:Carbon, timezone:string, recipient_type:string, recipient_payload:array<string,mixed>, account_id:int, template_id:int, name:string|null, variables_override:array<string,string>|null}
     */
    private function payload(SaveScheduledMessageRequest $request): array
    {
        $validated = $request->validated();

        return [
            'account_id' => (int) $validated['account_id'],
            'template_id' => (int) $validated['template_id'],
            'name' => $validated['name'] ?? null,
            'recipient_type' => $request->recipientType(),
            'recipient_payload' => $request->recipientPayload(),
            'variables_override' => $validated['overrides'] ?? null,
            'run_at' => $request->runAtUtc(),
            'timezone' => $request->timezone(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Workspace $workspace): array
    {
        return [
            'workspace' => $workspace,
            'accounts' => WhatsAppAccount::where('workspace_id', $workspace->id)
                ->where('status', AccountStatus::Connected->value)
                ->orderByDesc('is_default')
                ->get(['id', 'label', 'phone_number', 'is_default']),
            'templates' => Template::where('workspace_id', $workspace->id)
                ->where('status', TemplateStatus::Active->value)
                ->orderBy('name')
                ->get(['id', 'name', 'body', 'variables']),
            'tags' => ContactTag::where('workspace_id', $workspace->id)
                ->withCount('contacts')
                ->orderBy('name')
                ->get(['id', 'name']),
            'contacts' => Contact::where('workspace_id', $workspace->id)
                ->where('is_opted_out', false)
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'phone']),
            'contactCount' => Contact::where('workspace_id', $workspace->id)
                ->where('is_opted_out', false)
                ->count(),
        ];
    }

    private function ensureBelongsToCurrentWorkspace(ScheduledMessage $schedule): void
    {
        if ($schedule->workspace_id !== $this->current->id()) {
            abort(404);
        }
    }
}
