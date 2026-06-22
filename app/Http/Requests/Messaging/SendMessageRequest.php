<?php

declare(strict_types=1);

namespace App\Http\Requests\Messaging;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer'],
            'template_id' => ['required', 'integer'],
            'recipient_mode' => ['required', 'in:selected,tag,all'],
            'contact_ids' => ['array', 'required_if:recipient_mode,selected'],
            'contact_ids.*' => ['integer'],
            'tag_id' => ['nullable', 'integer', 'required_if:recipient_mode,tag'],
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
