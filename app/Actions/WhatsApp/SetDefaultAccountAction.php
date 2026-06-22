<?php

declare(strict_types=1);

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\DB;

final class SetDefaultAccountAction
{
    public function execute(WhatsAppAccount $account): void
    {
        DB::transaction(function () use ($account): void {
            WhatsAppAccount::where('workspace_id', $account->workspace_id)
                ->where('id', '!=', $account->id)
                ->update(['is_default' => false]);

            $account->update(['is_default' => true]);
        });
    }
}
