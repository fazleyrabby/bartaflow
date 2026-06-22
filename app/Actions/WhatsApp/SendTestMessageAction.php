<?php

declare(strict_types=1);

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\MessagePayload;
use App\Services\WhatsApp\SendResult;
use App\Services\WhatsApp\WhatsAppClient;

final class SendTestMessageAction
{
    public function __construct(private readonly WhatsAppClient $client) {}

    public function execute(WhatsAppAccount $account, string $to): SendResult
    {
        if (! $account->isConnected()) {
            return SendResult::fail('Account is not connected. Please connect the account first.');
        }

        $payload = new MessagePayload(
            to: $to,
            body: 'This is a test message from BartaFlow. Your WhatsApp account is connected successfully! 🎉',
        );

        return $this->client->send($account, $payload);
    }
}
