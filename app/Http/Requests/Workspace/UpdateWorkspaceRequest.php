<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = app(CurrentWorkspace::class)->get();

        return $this->user()->can('update', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'locale' => ['nullable', 'string', 'max:8'],
            'business_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'timezone.timezone' => 'Please select a valid timezone.',
        ];
    }
}
