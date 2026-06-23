<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\VerifyEmailNoticeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactTagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduledMessageController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TemplateController;
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
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Contacts
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::patch('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::post('/contacts/{contact}/toggle-opt-out', [ContactController::class, 'toggleOptOut'])->name('contacts.toggle-opt-out');
        Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import');

        // Contact tags (JSON for tag picker)
        Route::get('/tags', [ContactTagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [ContactTagController::class, 'store'])->name('tags.store');
        Route::patch('/tags/{tag}', [ContactTagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [ContactTagController::class, 'destroy'])->name('tags.destroy');

        // Templates (CRUD via resource + duplicate & live preview)
        Route::post('/templates/preview', [TemplateController::class, 'preview'])->name('templates.preview');
        Route::post('/templates/{template}/duplicate', [TemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::resource('templates', TemplateController::class)->except(['show']);

        // Messaging — compose, send now & logs (Task 009)
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
        Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::post('/messages/retry-bulk', [MessageController::class, 'bulkRetry'])->name('messages.retry-bulk');
        Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{message}/retry', [MessageController::class, 'retry'])->name('messages.retry');

        // Scheduling
        Route::get('/scheduling', [ScheduledMessageController::class, 'index'])->name('scheduling.index');
        Route::get('/scheduling/create', [ScheduledMessageController::class, 'create'])->name('scheduling.create');
        Route::post('/scheduling', [ScheduledMessageController::class, 'store'])->name('scheduling.store');
        Route::get('/scheduling/{scheduling}/edit', [ScheduledMessageController::class, 'edit'])->name('scheduling.edit');
        Route::put('/scheduling/{scheduling}', [ScheduledMessageController::class, 'update'])->name('scheduling.update');
        Route::post('/scheduling/{scheduling}/cancel', [ScheduledMessageController::class, 'cancel'])->name('scheduling.cancel');

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

            // Activity log (audit trail) — owner/admin only (policy-gated)
            Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity');

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
