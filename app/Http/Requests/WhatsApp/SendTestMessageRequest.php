<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Http\FormRequest;

class SendTestMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var WhatsAppAccount $account */
        $account = $this->route('account');

        return $this->user()->can('sendTest', $account);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
        ];
    }
}
