<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Creates a workspace and assigns the given user as Owner — atomically.
// Used on registration and when an existing user creates a new workspace (task 003).
// See docs/architecture.md §6 (Action Classes) and docs/tasks/002-authentication.md.
final class CreateWorkspaceAction
{
    public function execute(User $user, string $name): Workspace
    {
        return DB::transaction(function () use ($user, $name) {
            $workspace = Workspace::create([
                'owner_id' => $user->id,
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'timezone' => config('bartaflow.defaults.timezone', 'Asia/Dhaka'),
                'locale' => config('bartaflow.defaults.locale', 'en'),
                'status' => 'active',
            ]);

            $workspace->users()->attach($user->id, [
                'role' => Role::Owner->value,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return $workspace;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'workspace';
        }

        $slug = $base;
        $i = 1;

        while (Workspace::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
