<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

final class CloudApiWhatsAppClient implements WhatsAppClient
{
    private string $baseUrl;
    private string $version;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.whatsapp.base_url', 'https://graph.facebook.com'), '/');
        $this->version = (string) config('services.whatsapp.version', 'v19.0');
    }

    public function send(WhatsAppAccount $account, MessagePayload $payload): SendResult
    {
        if (! $account->isConnected()) {
            return SendResult::fail('Account is not connected.');
        }

        try {
            $response = Http::withToken($account->access_token)
                ->timeout(10)
                ->post("{$this->baseUrl}/{$this->version}/{$account->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $payload->to,
                    'type'              => 'text',
                    'text'              => ['body' => $payload->body],
                ]);

            if ($response->status() === 429) {
                return SendResult::fail('Rate limit exceeded. Please try again later.', retryable: true);
            }

            if ($response->serverError()) {
                return SendResult::fail('WhatsApp API server error. Please try again.', retryable: true);
            }

            if ($response->clientError()) {
                $error = $response->json('error.message', 'Invalid request.');

                return SendResult::fail((string) $error, retryable: false);
            }

            $messageId = $response->json('messages.0.id', '');

            return SendResult::ok((string) $messageId);
        } catch (ConnectionException) {
            return SendResult::fail('Could not connect to WhatsApp API.', retryable: true);
        } catch (RequestException $e) {
            return SendResult::fail($e->getMessage(), retryable: false);
        }
    }

    public function verifyCredentials(WhatsAppAccount $account): VerifyResult
    {
        try {
            $response = Http::withToken($account->access_token)
                ->timeout(10)
                ->get("{$this->baseUrl}/{$this->version}/{$account->phone_number_id}");

            if ($response->status() === 401 || $response->status() === 403) {
                return VerifyResult::fail('Invalid or expired access token.');
            }

            if ($response->clientError()) {
                $error = $response->json('error.message', 'Invalid credentials.');

                return VerifyResult::fail((string) $error);
            }

            if ($response->serverError() || $response->status() === 429) {
                return VerifyResult::fail('WhatsApp API temporarily unavailable.');
            }

            return VerifyResult::ok();
        } catch (ConnectionException) {
            return VerifyResult::fail('Could not connect to WhatsApp API.');
        }
    }
}
