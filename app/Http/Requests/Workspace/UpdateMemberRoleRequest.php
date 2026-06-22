<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Enums\Role;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', app(CurrentWorkspace::class)->get());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $assignable = array_map(fn (Role $r) => $r->value, Role::assignable());

        return [
            'role' => ['required', Rule::in($assignable)],
        ];
    }
}
