<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Models\WhatsAppAccount;
use App\Notifications\WhatsAppAccountErrorNotification;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Console\Command;

class WhatsAppHealthCheckCommand extends Command
{
    protected $signature = 'accounts:health-check';

    protected $description = 'Re-verify all connected WhatsApp accounts and flag errors.';

    public function handle(WhatsAppClient $client): int
    {
        $accounts = WhatsAppAccount::withoutGlobalScopes()
            ->where('status', AccountStatus::Connected->value)
            ->get();

        $this->info("Checking {$accounts->count()} connected account(s)...");

        foreach ($accounts as $account) {
            $result = $client->verifyCredentials($account);

            $account->update(['last_checked_at' => now()]);

            if (! $result->success) {
                $account->update([
                    'status' => AccountStatus::Error->value,
                    'status_reason' => $result->error,
                ]);

                // Notify all admins and owner of the workspace.
                $workspace = $account->workspace;
                $workspace->users()
                    ->wherePivotIn('role', ['owner', 'admin'])
                    ->get()
                    ->each(fn ($user) => $user->notify(
                        new WhatsAppAccountErrorNotification($account, (string) $result->error)
                    ));

                $this->warn("Account [{$account->label}] → ERROR: {$result->error}");
            } else {
                $this->line("Account [{$account->label}] → OK");
            }
        }

        $this->info('Health check complete.');

        return self::SUCCESS;
    }
}
