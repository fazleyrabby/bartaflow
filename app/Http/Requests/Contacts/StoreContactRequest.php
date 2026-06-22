<?php

declare(strict_types=1);

namespace App\Http\Requests\Contacts;

use App\Models\Contact;
use App\Services\Tenancy\CurrentWorkspace;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string|object>> */
    public function rules(): array
    {
        $workspaceId = app(CurrentWorkspace::class)->id();

        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Contact::class, 'phone')
                    ->where('workspace_id', $workspaceId),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:contact_tags,id'],
        ];
    }

    /** @return array<string, mixed> */
    public function validatedWithNormalizedPhone(): array
    {
        $data = $this->validated();

        if (isset($data['phone'])) {
            $data['phone'] = (string) PhoneNumber::fromInput($data['phone']);
        }

        return $data;
    }
}
