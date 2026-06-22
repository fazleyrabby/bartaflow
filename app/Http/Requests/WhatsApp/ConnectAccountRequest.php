<?php

declare(strict_types=1);

namespace App\Http\Requests\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;

class ConnectAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = app(CurrentWorkspace::class)->get();

        return $this->user()->can('create', [
            WhatsAppAccount::class,
            $workspace->id,
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'min:2', 'max:60'],
            'phone_number' => ['required', 'string', 'max:20'],
            'phone_number_id' => ['required', 'string', 'max:64'],
            'business_account_id' => ['required', 'string', 'max:64'],
            'access_token' => ['required', 'string', 'min:10'],
        ];
    }
}
