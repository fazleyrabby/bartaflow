<?php

declare(strict_types=1);

namespace App\Http\Requests\Templates;

use App\Enums\TemplateCategory;
use App\Enums\TemplateStatus;
use App\Models\Template;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique(Template::class, 'name')
                    ->where('workspace_id', $workspaceId)
                    ->withoutTrashed(),
            ],
            'category' => ['required', Rule::enum(TemplateCategory::class)],
            'body' => ['required', 'string', 'max:4096'],
            'language' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::enum(TemplateStatus::class)],
        ];
    }
}
