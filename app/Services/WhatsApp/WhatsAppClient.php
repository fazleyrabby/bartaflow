<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;

interface WhatsAppClient
{
    public function send(WhatsAppAccount $account, MessagePayload $payload): SendResult;

    public function verifyCredentials(WhatsAppAccount $account): VerifyResult;
}
