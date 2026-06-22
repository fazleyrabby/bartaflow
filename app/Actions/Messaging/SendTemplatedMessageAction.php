<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Enums\MessageStatus;
use App\Exceptions\MessageSendException;
use App\Jobs\SendMessageJob;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Template;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Services\Messaging\DispatchResult;
use App\Services\Messaging\SendTemplatedMessageData;
use App\Services\Templates\TemplateRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SendTemplatedMessageAction
{
    public function __construct(private readonly TemplateRenderer $renderer) {}

    public function execute(Workspace $workspace, User $user, SendTemplatedMessageData $data): DispatchResult
    {
        // ── Block rules ───────────────────────────────────────────────────────
        if ($workspace->isSuspended()) {
            throw MessageSendException::workspaceSuspended();
        }

        if (! $user->hasVerifiedEmail()) {
            throw MessageSendException::userUnverified();
        }

        $account = WhatsAppAccount::where('workspace_id', $workspace->id)
            ->find($data->accountId);

        if ($account === null || ! $account->isConnected()) {
            throw MessageSendException::accountNotConnected();
        }

        $template = Template::where('workspace_id', $workspace->id)
            ->find($data->templateId);

        if ($template === null) {
            throw new MessageSendException('The selected template could not be found.');
        }

        // ── Resolve & filter recipients ───────────────────────────────────────
        $candidates = $this->resolveContacts($workspace, $data);

        $skipped = ['opted_out' => 0, 'missing_variables' => 0, 'duplicate' => 0];
        $seenPhones = [];
        $rows = [];

        foreach ($candidates as $contact) {
            if ($contact->is_opted_out) {
                $skipped['opted_out']++;

                continue;
            }

            if (isset($seenPhones[$contact->phone])) {
                $skipped['duplicate']++;

                continue;
            }
            $seenPhones[$contact->phone] = true;

            $render = $this->renderer->render($template->body, $contact, $workspace, $data->overrides);

            if ($render->hasMissing()) {
                $skipped['missing_variables']++;

                continue;
            }

            $rows[] = [
                'contact' => $contact,
                'body' => $render->text,
            ];
        }

        if ($rows === []) {
            throw MessageSendException::noRecipients();
        }

        // ── Persist + dispatch (atomic) ───────────────────────────────────────
        $messageIds = DB::transaction(function () use ($workspace, $account, $template, $rows): array {
            $ids = [];

            foreach ($rows as $row) {
                /** @var Contact $contact */
                $contact = $row['contact'];

                $message = Message::create([
                    'workspace_id' => $workspace->id,
                    'whatsapp_account_id' => $account->id,
                    'template_id' => $template->id,
                    'contact_id' => $contact->id,
                    'recipient_phone' => $contact->phone,
                    'recipient_name' => $contact->name,
                    'body' => $row['body'],
                    'variables_used' => $template->variables,
                    'direction' => 'outbound',
                    'status' => MessageStatus::Queued,
                    'attempts' => 0,
                    'idempotency_key' => (string) Str::uuid(),
                    'queued_at' => now(),
                ]);

                $ids[] = $message->id;
            }

            return $ids;
        });

        foreach ($messageIds as $id) {
            SendMessageJob::dispatch($id);
        }

        return new DispatchResult(
            queued: count($messageIds),
            skipped: array_sum($skipped),
            skippedReasons: array_filter($skipped),
        );
    }

    /**
     * @return Collection<int, Contact>
     */
    private function resolveContacts(Workspace $workspace, SendTemplatedMessageData $data)
    {
        $query = Contact::where('workspace_id', $workspace->id);

        return match ($data->recipientMode) {
            'selected' => $query->whereIn('id', $data->contactIds)->get(),
            'tag' => $query->whereHas('tags', fn ($q) => $q->where('contact_tags.id', $data->tagId))->get(),
            'all' => $query->get(),
        };
    }
}
