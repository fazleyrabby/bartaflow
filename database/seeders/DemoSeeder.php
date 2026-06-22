<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Workspaces\CreateWorkspaceAction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(CreateWorkspaceAction $action): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name'              => 'Demo Admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'phone'             => null,
                'last_login_at'     => null,
            ]
        );

        if ($user->workspaces()->doesntExist()) {
            $action->execute($user, 'Demo Store');
        }
    }
}
