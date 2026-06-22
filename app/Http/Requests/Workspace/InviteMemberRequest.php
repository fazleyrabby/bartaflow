<?php

declare(strict_types=1);

namespace App\Http\Requests\Workspace;

use App\Enums\Role;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = app(CurrentWorkspace::class)->get();

        return $this->user()->can('invite', [\App\Models\WorkspaceUser::class, $workspace]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $assignable = array_map(fn (Role $r) => $r->value, Role::assignable());

        return [
            'email' => ['required', 'string', 'email', 'max:180'],
            'role'  => ['required', Rule::in($assignable)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'Enter a valid email address.',
            'role.in'        => 'Role must be Admin or Staff.',
        ];
    }
}
