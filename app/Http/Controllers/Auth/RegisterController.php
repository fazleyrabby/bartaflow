<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Workspaces\CreateWorkspaceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, CreateWorkspaceAction $action): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $action): User {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password, // cast to hashed by the model
            ]);

            $workspaceName = $request->filled('workspace_name')
                ? $request->string('workspace_name')->toString()
                : "{$request->name}'s Workspace";

            $action->execute($user, $workspaceName);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
