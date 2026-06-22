<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\VerifyEmailNoticeController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WhatsAppAccountController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceSwitcherController;
use Illuminate\Support\Facades\Route;

// Health check — no auth required. See docs/tasks/001-foundation.md.
Route::get('/up', HealthController::class)->name('health');

// ─────────────────────────────────────────────────────────────────────────────
// Guest-only routes (redirect to dashboard if already authenticated)
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    // Throttle is handled inside LoginRequest::authenticate() (5 attempts per email+ip).
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

// ─────────────────────────────────────────────────────────────────────────────
// Public invitation page (no auth — allows guests to view before logging in)
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');

// ─────────────────────────────────────────────────────────────────────────────
// Authenticated routes
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', LogoutController::class)->name('logout');

    // Email verification
    Route::get('/verify-email', VerifyEmailNoticeController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', VerificationNotificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Accept invitation (auth + verified, but no workspace middleware — user may not have one yet)
    Route::middleware('verified')->group(function () {
        Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
            ->name('invitations.accept');
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Verified + workspace-scoped routes
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware(['verified', 'workspace'])->group(function () {
        // Dashboard
        Route::view('/dashboard', 'dashboard')->name('dashboard');

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Workspace switcher
        Route::post('/workspaces/switch', WorkspaceSwitcherController::class)->name('workspaces.switch');

        // ─── Settings ────────────────────────────────────────────────────────
        Route::prefix('settings')->name('settings.')->group(function () {
            // Workspace settings (owner + admin can edit; owner can delete/transfer)
            Route::get('/workspace', [WorkspaceController::class, 'edit'])->name('workspace');
            Route::patch('/workspace', [WorkspaceController::class, 'update'])->name('workspace.update');
            Route::post('/workspace/transfer', [WorkspaceController::class, 'transfer'])->name('workspace.transfer');
            Route::delete('/workspace', [WorkspaceController::class, 'destroy'])->name('workspace.destroy');

            // Team management (owner + admin)
            Route::get('/team', [TeamController::class, 'index'])->name('team');
            Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
            Route::patch('/team/{membership}/role', [TeamController::class, 'updateRole'])->name('team.role');
            Route::delete('/team/{membership}', [TeamController::class, 'remove'])->name('team.remove');

            // Invitation actions from team page
            Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])
                ->name('invitations.resend');
            Route::delete('/invitations/{invitation}', [InvitationController::class, 'revoke'])
                ->name('invitations.revoke');

            // WhatsApp Accounts
            Route::get('/whatsapp', [WhatsAppAccountController::class, 'index'])->name('whatsapp');
            Route::get('/whatsapp/connect', [WhatsAppAccountController::class, 'create'])->name('whatsapp.create');
            Route::post('/whatsapp', [WhatsAppAccountController::class, 'store'])->name('whatsapp.store');
            Route::get('/whatsapp/{account}/edit', [WhatsAppAccountController::class, 'edit'])->name('whatsapp.edit');
            Route::patch('/whatsapp/{account}', [WhatsAppAccountController::class, 'update'])->name('whatsapp.update');
            Route::post('/whatsapp/{account}/test', [WhatsAppAccountController::class, 'sendTest'])->name('whatsapp.test');
            Route::post('/whatsapp/{account}/disconnect', [WhatsAppAccountController::class, 'disconnect'])->name('whatsapp.disconnect');
            Route::post('/whatsapp/{account}/default', [WhatsAppAccountController::class, 'setDefault'])->name('whatsapp.default');
        });
    });
});

// Landing page — public
Route::view('/', 'welcome')->name('home');
