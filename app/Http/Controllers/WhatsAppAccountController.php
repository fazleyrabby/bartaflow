<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\WhatsApp\ConnectWhatsAppAccountAction;
use App\Actions\WhatsApp\DisconnectAccountAction;
use App\Actions\WhatsApp\SendTestMessageAction;
use App\Actions\WhatsApp\SetDefaultAccountAction;
use App\Actions\WhatsApp\UpdateWhatsAppAccountAction;
use App\Http\Requests\WhatsApp\ConnectAccountRequest;
use App\Http\Requests\WhatsApp\SendTestMessageRequest;
use App\Http\Requests\WhatsApp\UpdateAccountRequest;
use App\Models\WhatsAppAccount;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WhatsAppAccountController extends Controller
{
    public function __construct(private readonly CurrentWorkspace $current) {}

    public function index(): View
    {
        $workspace = $this->current->get();
        $this->authorize('viewAny', [\App\Models\WhatsAppAccount::class, $workspace->id]);

        $accounts = WhatsAppAccount::where('workspace_id', $workspace->id)
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        return view('settings.whatsapp.index', compact('accounts', 'workspace'));
    }

    public function create(): View
    {
        $workspace = $this->current->get();
        $this->authorize('create', [\App\Models\WhatsAppAccount::class, $workspace->id]);

        return view('settings.whatsapp.create');
    }

    public function store(ConnectAccountRequest $request, ConnectWhatsAppAccountAction $action): RedirectResponse
    {
        $workspace = $this->current->get();
        $account   = $action->execute($workspace, $request->validated());

        return redirect()->route('settings.whatsapp')
            ->with('status', "Account \"{$account->label}\" connected. Status: {$account->status->label()}.");
    }

    public function edit(WhatsAppAccount $account): View
    {
        $this->ensureBelongsToCurrentWorkspace($account);
        $this->authorize('update', $account);

        return view('settings.whatsapp.edit', compact('account'));
    }

    public function update(UpdateAccountRequest $request, WhatsAppAccount $account, UpdateWhatsAppAccountAction $action): RedirectResponse
    {
        $this->ensureBelongsToCurrentWorkspace($account);
        $action->execute($account, $request->validated());

        return redirect()->route('settings.whatsapp')
            ->with('status', 'Account updated successfully.');
    }

    public function sendTest(SendTestMessageRequest $request, WhatsAppAccount $account, SendTestMessageAction $action): JsonResponse
    {
        $this->ensureBelongsToCurrentWorkspace($account);
        $result = $action->execute($account, $request->string('to')->toString());

        if ($result->success) {
            return response()->json(['message' => 'Test message sent successfully!', 'id' => $result->messageId]);
        }

        return response()->json(['error' => $result->error], 422);
    }

    public function disconnect(WhatsAppAccount $account, DisconnectAccountAction $action): RedirectResponse
    {
        $this->authorize('delete', $account);
        $this->ensureBelongsToCurrentWorkspace($account);
        $action->execute($account);

        return redirect()->route('settings.whatsapp')
            ->with('status', "Account \"{$account->label}\" disconnected.");
    }

    public function setDefault(WhatsAppAccount $account, SetDefaultAccountAction $action): RedirectResponse
    {
        $this->authorize('setDefault', $account);
        $this->ensureBelongsToCurrentWorkspace($account);
        $action->execute($account);

        return redirect()->route('settings.whatsapp')
            ->with('status', "\"{$account->label}\" is now the default sender.");
    }

    private function ensureBelongsToCurrentWorkspace(WhatsAppAccount $account): void
    {
        if ($account->workspace_id !== $this->current->id()) {
            abort(404);
        }
    }
}
