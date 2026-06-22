<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;

class TransferOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = app(CurrentWorkspace::class)->get();

        return $this->user()->can('transferOwnership', $workspace);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
