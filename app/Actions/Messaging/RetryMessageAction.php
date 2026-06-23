<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Enums\MessageStatus;
use App\Jobs\SendMessageJob;
use App\Models\Message;

final class RetryMessageAction
{
    /**
     * Re-queue a single failed message. Returns false (no-op) if it is not failed.
     */
    public function retry(Message $message): bool
    {
        if ($message->status !== MessageStatus::Failed) {
            return false;
        }

        $message->forceFill([
            'status' => MessageStatus::Queued,
            'error_code' => null,
            'error_message' => null,
            'failed_at' => null,
            'queued_at' => now(),
        ])->save();

        SendMessageJob::dispatch($message->id);

        return true;
    }

    /**
     * Bulk-retry: only failed messages within the given workspace are re-queued.
     *
     * @param  array<int>  $ids
     * @return int number of messages re-queued
     */
    public function retryMany(int $workspaceId, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $messages = Message::where('workspace_id', $workspaceId)
            ->whereIn('id', $ids)
            ->where('status', MessageStatus::Failed)
            ->get();

        foreach ($messages as $message) {
            $this->retry($message);
        }

        return $messages->count();
    }
}
