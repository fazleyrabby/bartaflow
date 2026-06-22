<?php

declare(strict_types=1);

namespace App\Actions\Scheduling;

use App\Actions\Messaging\SendTemplatedMessageAction;
use App\Enums\ScheduleStatus;
use App\Exceptions\MessageSendException;
use App\Models\ScheduledMessage;
use App\Models\User;
use App\Services\Messaging\SendTemplatedMessageData;
use Illuminate\Support\Facades\DB;

final class DispatchDueSchedulesAction
{
    public function __construct(private readonly SendTemplatedMessageAction $sender) {}

    /**
     * Claim and dispatch all due pending schedules.
     *
     * @return array{dispatched:int, failed:int, skipped:int}
     */
    public function execute(): array
    {
        $graceHours = (int) config('services.whatsapp.schedule_grace_hours', 24);

        $claimedIds = $this->claimDue();

        $dispatched = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($claimedIds as $id) {
            /** @var ScheduledMessage|null $schedule */
            $schedule = ScheduledMessage::find($id);

            if ($schedule === null) {
                continue;
            }

            // Overdue beyond the grace window → fail without sending.
            if ($schedule->run_at->copy()->addHours($graceHours)->isPast()) {
                $schedule->update([
                    'status' => ScheduleStatus::Failed,
                    'last_error' => 'Skipped: more than '.$graceHours.'h overdue.',
                    'processed_at' => now(),
                ]);
                $skipped++;

                continue;
            }

            try {
                $this->dispatchOne($schedule);
                $schedule->update([
                    'status' => ScheduleStatus::Sent,
                    'last_error' => null,
                    'processed_at' => now(),
                ]);
                $dispatched++;
            } catch (MessageSendException $e) {
                $schedule->update([
                    'status' => ScheduleStatus::Failed,
                    'last_error' => mb_substr($e->getMessage(), 0, 255),
                    'processed_at' => now(),
                ]);
                $failed++;
            }
        }

        return ['dispatched' => $dispatched, 'failed' => $failed, 'skipped' => $skipped];
    }

    /**
     * Atomically move due pending schedules to "processing" so concurrent
     * runs never claim the same rows.
     *
     * @return list<int>
     */
    private function claimDue(): array
    {
        return DB::transaction(function (): array {
            $ids = ScheduledMessage::query()
                ->where('status', ScheduleStatus::Pending->value)
                ->where('run_at', '<=', now())
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                ScheduledMessage::whereIn('id', $ids)
                    ->update(['status' => ScheduleStatus::Processing->value]);
            }

            return $ids;
        });
    }

    private function dispatchOne(ScheduledMessage $schedule): void
    {
        $workspace = $schedule->workspace;
        $user = ($schedule->created_by ? User::find($schedule->created_by) : null) ?? $workspace->owner;

        if ($user === null) {
            throw new MessageSendException('No valid sender for this schedule.');
        }

        $this->sender->execute(
            $workspace,
            $user,
            $this->toSendData($schedule),
            $schedule->id,
        );
    }

    private function toSendData(ScheduledMessage $schedule): SendTemplatedMessageData
    {
        $payload = $schedule->recipient_payload;

        [$mode, $contactIds, $tagId] = match ($schedule->recipient_type) {
            'tag' => ['tag', [], (int) ($payload['tag_id'] ?? 0)],
            'filter' => ['all', [], null],
            default => ['selected', array_map('intval', $payload['contact_ids'] ?? []), null],
        };

        return new SendTemplatedMessageData(
            accountId: (int) $schedule->whatsapp_account_id,
            templateId: (int) $schedule->template_id,
            recipientMode: $mode,
            contactIds: $contactIds,
            tagId: $tagId,
            overrides: $schedule->variables_override ?? [],
        );
    }
}
