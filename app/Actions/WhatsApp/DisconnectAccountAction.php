<?php

declare(strict_types=1);

namespace App\Actions\WhatsApp;

use App\Enums\AccountStatus;
use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\DB;

final class DisconnectAccountAction
{
    public function execute(WhatsAppAccount $account): void
    {
        DB::transaction(function () use ($account): void {
            $wasDefault = $account->is_default;

            $account->update([
                'status'        => AccountStatus::Disconnected->value,
                'status_reason' => 'Manually disconnected.',
                'is_default'    => false,
            ]);

            // Promote the next connected account as default if this was the default.
            if ($wasDefault) {
                $next = WhatsAppAccount::where('workspace_id', $account->workspace_id)
                    ->where('id', '!=', $account->id)
                    ->where('status', AccountStatus::Connected->value)
                    ->oldest()
                    ->first();

                $next?->update(['is_default' => true]);
            }
        });
    }
}
