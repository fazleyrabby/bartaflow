<?php

declare(strict_types=1);

namespace App\Actions\WhatsApp;

use App\Enums\AccountStatus;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Support\Facades\DB;

final class ConnectWhatsAppAccountAction
{
    public function __construct(private readonly WhatsAppClient $client) {}

    /** @param array<string, mixed> $data */
    public function execute(Workspace $workspace, array $data): WhatsAppAccount
    {
        return DB::transaction(function () use ($workspace, $data): WhatsAppAccount {
            $account = WhatsAppAccount::create([
                'workspace_id'        => $workspace->id,
                'label'               => $data['label'],
                'provider'            => 'cloud_api',
                'phone_number'        => $data['phone_number'],
                'phone_number_id'     => $data['phone_number_id'] ?? null,
                'business_account_id' => $data['business_account_id'] ?? null,
                'access_token'        => $data['access_token'],
                'status'              => AccountStatus::Pending->value,
                'is_default'          => false,
            ]);

            // Verify credentials immediately.
            $result = $this->client->verifyCredentials($account);

            $account->update([
                'status'         => $result->success ? AccountStatus::Connected->value : AccountStatus::Error->value,
                'status_reason'  => $result->error,
                'last_checked_at' => now(),
            ]);

            // If this is the first account in the workspace, mark it as default.
            $count = WhatsAppAccount::where('workspace_id', $workspace->id)->count();
            if ($count === 1) {
                $account->update(['is_default' => true]);
            }

            return $account->fresh();
        });
    }
}
