<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\AccountStatus;
use App\Enums\MessageStatus;
use App\Enums\ScheduleStatus;
use App\Enums\TemplateStatus;
use App\Models\Contact;
use App\Models\Message;
use App\Models\ScheduledMessage;
use App\Models\Template;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Aggregates the workspace dashboard view-model. KPI aggregates are cached for a
 * short TTL and invalidated by the message/schedule observers on relevant writes.
 */
final class DashboardMetrics
{
    private const TTL_SECONDS = 60;

    /** Statuses that count as a successful outbound send. */
    private const SENT_STATUSES = ['sent', 'delivered', 'read'];

    /**
     * Cached KPI counters for the workspace.
     *
     * @return array{sent_today:int, scheduled_upcoming:int, failed_24h:int, total_contacts:int, active_templates:int, connected_accounts:int, account_status:AccountStatus}
     */
    public function kpis(Workspace $workspace): array
    {
        return Cache::remember(self::cacheKey($workspace->id), self::TTL_SECONDS, function () use ($workspace): array {
            $startOfDay = Carbon::now($workspace->timezone)->startOfDay()->utc();

            $connected = WhatsAppAccount::where('workspace_id', $workspace->id)
                ->where('status', AccountStatus::Connected->value)
                ->count();

            $default = WhatsAppAccount::where('workspace_id', $workspace->id)
                ->orderByDesc('is_default')
                ->first();

            return [
                'sent_today' => Message::where('workspace_id', $workspace->id)
                    ->whereIn('status', self::SENT_STATUSES)
                    ->where('sent_at', '>=', $startOfDay)
                    ->count(),
                'scheduled_upcoming' => ScheduledMessage::where('workspace_id', $workspace->id)
                    ->where('status', ScheduleStatus::Pending->value)
                    ->where('run_at', '>=', now())
                    ->count(),
                'failed_24h' => Message::where('workspace_id', $workspace->id)
                    ->where('status', MessageStatus::Failed->value)
                    ->where('failed_at', '>=', now()->subDay())
                    ->count(),
                'total_contacts' => Contact::where('workspace_id', $workspace->id)->count(),
                'active_templates' => Template::where('workspace_id', $workspace->id)
                    ->where('status', TemplateStatus::Active->value)
                    ->count(),
                'connected_accounts' => $connected,
                'account_status' => $default !== null ? $default->status : AccountStatus::Pending,
            ];
        });
    }

    /**
     * Live onboarding checklist (not cached — reflects real-time state).
     *
     * @return array{steps: list<array{key:string, label:string, done:bool, route:string}>, complete:bool}
     */
    public function checklist(Workspace $workspace): array
    {
        $accountConnected = WhatsAppAccount::where('workspace_id', $workspace->id)
            ->where('status', AccountStatus::Connected->value)
            ->exists();

        $hasContacts = Contact::where('workspace_id', $workspace->id)->exists();
        $hasTemplate = Template::where('workspace_id', $workspace->id)->exists();
        $hasSent = Message::where('workspace_id', $workspace->id)
            ->whereIn('status', self::SENT_STATUSES)
            ->exists();

        $steps = [
            ['key' => 'account', 'label' => 'Connect a WhatsApp account', 'done' => $accountConnected, 'route' => route('settings.whatsapp.create')],
            ['key' => 'contacts', 'label' => 'Add your contacts', 'done' => $hasContacts, 'route' => route('contacts.index')],
            ['key' => 'template', 'label' => 'Create a message template', 'done' => $hasTemplate, 'route' => route('templates.create')],
            ['key' => 'send', 'label' => 'Send your first message', 'done' => $hasSent, 'route' => route('messages.create')],
        ];

        return [
            'steps' => $steps,
            'complete' => $accountConnected && $hasContacts && $hasTemplate && $hasSent,
        ];
    }

    /**
     * Latest outbound messages for the activity feed.
     *
     * @return Collection<int, Message>
     */
    public function recentMessages(Workspace $workspace, int $limit = 8): Collection
    {
        return Message::where('workspace_id', $workspace->id)
            ->with(['template:id,name'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Upcoming pending schedules, soonest first.
     *
     * @return Collection<int, ScheduledMessage>
     */
    public function upcomingSchedules(Workspace $workspace, int $limit = 5): Collection
    {
        return ScheduledMessage::where('workspace_id', $workspace->id)
            ->with(['template:id,name'])
            ->where('status', ScheduleStatus::Pending->value)
            ->where('run_at', '>=', now())
            ->orderBy('run_at')
            ->limit($limit)
            ->get();
    }

    public static function forget(int $workspaceId): void
    {
        Cache::forget(self::cacheKey($workspaceId));
    }

    private static function cacheKey(int $workspaceId): string
    {
        return "dashboard:kpis:{$workspaceId}";
    }
}
