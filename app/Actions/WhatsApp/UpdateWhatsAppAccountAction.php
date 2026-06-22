<?php

declare(strict_types=1);

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppAccount;

final class UpdateWhatsAppAccountAction
{
    /** @param array<string, mixed> $data */
    public function execute(WhatsAppAccount $account, array $data): WhatsAppAccount
    {
        $fillable = array_filter([
            'label'               => $data['label'] ?? null,
            'phone_number'        => $data['phone_number'] ?? null,
            'phone_number_id'     => $data['phone_number_id'] ?? null,
            'business_account_id' => $data['business_account_id'] ?? null,
        ], fn ($v) => $v !== null);

        // Only update token if a new one was provided (write-only field).
        if (! empty($data['access_token'])) {
            $fillable['access_token'] = $data['access_token'];
        }

        $account->update($fillable);

        return $account->fresh();
    }
}
