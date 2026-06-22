<?php

declare(strict_types=1);

namespace App\Http\Requests\Scheduling;

use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class SaveScheduledMessageRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:120'],
            'recipient_mode' => ['required', 'in:selected,tag,all'],
            'contact_ids' => ['array', 'required_if:recipient_mode,selected'],
            'contact_ids.*' => ['integer'],
            'tag_id' => ['nullable', 'integer', 'required_if:recipient_mode,tag'],
            'run_at' => ['required', 'date'],
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Ensure run_at (interpreted in the workspace timezone) is in the future.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('run_at')) {
                return;
            }

            if ($this->runAtUtc()->isPast()) {
                $validator->errors()->add('run_at', 'The scheduled time must be in the future.');
            }
        });
    }

    public function timezone(): string
    {
        return app(CurrentWorkspace::class)->get()->timezone;
    }

    /**
     * Convert the user-entered local datetime (workspace tz) to a UTC instant.
     */
    public function runAtUtc(): Carbon
    {
        return Carbon::parse($this->input('run_at'), $this->timezone())->utc();
    }

    public function recipientType(): string
    {
        return match ($this->input('recipient_mode')) {
            'tag' => 'tag',
            'all' => 'filter',
            default => 'contacts',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function recipientPayload(): array
    {
        return match ($this->input('recipient_mode')) {
            'tag' => ['tag_id' => (int) $this->input('tag_id')],
            'all' => ['mode' => 'all'],
            default => ['contact_ids' => array_map('intval', $this->input('contact_ids', []))],
        };
    }
}
