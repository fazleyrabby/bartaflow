<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var WhatsAppAccount $account */
        $account = $this->route('account');

        return $this->user()->can('update', $account);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'min:2', 'max:60'],
            'phone_number' => ['required', 'string', 'max:20'],
            'phone_number_id' => ['required', 'string', 'max:64'],
            'business_account_id' => ['required', 'string', 'max:64'],
            'access_token' => ['nullable', 'string', 'min:10'],
        ];
    }
}
