<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Message;
use App\Models\Template;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Observers\TemplateObserver;
use App\Policies\ContactPolicy;
use App\Policies\ContactTagPolicy;
use App\Policies\MembershipPolicy;
use App\Policies\MessagePolicy;
use App\Policies\TemplatePolicy;
use App\Policies\WhatsAppAccountPolicy;
use App\Policies\WorkspacePolicy;
use App\Services\Tenancy\CurrentWorkspace;
use App\Services\WhatsApp\CloudApiWhatsAppClient;
use App\Services\WhatsApp\FakeWhatsAppClient;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // CurrentWorkspace is request-scoped — singleton is safe because PHP-FPM
        // creates a fresh container per request, and tests use fresh app instances.
        $this->app->singleton(CurrentWorkspace::class);

        // Bind WhatsAppClient: fake in testing/local, real in production.
        $this->app->bind(WhatsAppClient::class, function () {
            if (app()->environment(['testing', 'local'])) {
                return new FakeWhatsAppClient;
            }

            return new CloudApiWhatsAppClient;
        });
    }

    public function boot(): void
    {
        // Strict model behaviour: flag N+1, missing attributes, and silent discards
        // outside production so they surface in development and CI.
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(WorkspaceUser::class, MembershipPolicy::class);
        Gate::policy(WhatsAppAccount::class, WhatsAppAccountPolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(ContactTag::class, ContactTagPolicy::class);
        Gate::policy(Template::class, TemplatePolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);

        Template::observe(TemplateObserver::class);

        $this->configureRateLimiting();
        $this->configureViewComposers();
    }

    // Login throttle: 5 attempts per minute per email+ip.
    // The named limiter is no longer applied as route middleware (B3 fix).
    // It remains here in case it's useful for other contexts.
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->string('email')->lower()->value().'|'.$request->ip()
            );
        });

        // Outbound WhatsApp throttle for SendMessageJob (per second, configurable).
        RateLimiter::for('whatsapp-send', function () {
            $perSecond = (int) config('services.whatsapp.rate_per_second', 10);

            return Limit::perSecond(max(1, $perSecond));
        });
    }

    private function configureViewComposers(): void
    {
        View::composer('components.app-layout', function (\Illuminate\View\View $view) {
            if (! auth()->check()) {
                return;
            }

            $current = app(CurrentWorkspace::class);
            $user = auth()->user();
            $userWorkspaces = $user?->workspaces()->select(['workspaces.id', 'workspaces.name'])->get();

            $view->with([
                'userName' => $user?->name,
                'workspaceName' => $current->isSet() ? $current->get()->name : 'My Workspace',
                'userWorkspaces' => $userWorkspaces ?? collect(),
                'currentWorkspaceId' => $current->isSet() ? $current->id() : null,
            ]);
        });
    }
}
