<x-app-layout>
    <x-slot:title>Dashboard</x-slot:title>
    <x-slot:header>Dashboard</x-slot:header>
    <x-slot:subheader>{{ $workspace->name }} — your messaging at a glance.</x-slot:subheader>

    <x-slot:actions>
        <x-button :href="route('messages.create')">New message</x-button>
    </x-slot:actions>

    {{-- ── Onboarding checklist (only until complete) ─────────────────────── --}}
    @unless ($checklist['complete'])
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5"
             x-data="{ dismissed: false }" x-show="!dismissed">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-base font-semibold text-emerald-900">Finish setting up your workspace</h3>
                    <p class="mt-1 text-sm text-emerald-700">Complete these steps to start sending messages.</p>
                </div>
                <button type="button" @click="dismissed = true" class="text-emerald-500 hover:text-emerald-700" aria-label="Dismiss">&times;</button>
            </div>

            <ul class="mt-4 space-y-2">
                @foreach ($checklist['steps'] as $step)
                    <li class="flex items-center gap-3">
                        @if ($step['done'])
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs text-white">✓</span>
                            <span class="text-sm text-emerald-900 line-through">{{ $step['label'] }}</span>
                        @else
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-emerald-300 text-xs text-emerald-400"></span>
                            <a href="{{ $step['route'] }}" class="text-sm font-medium text-emerald-700 hover:underline">{{ $step['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endunless

    {{-- ── KPI cards ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-kpi label="Sent today" :value="$kpis['sent_today']" accent="green" :href="route('messages.index')" />
        <x-kpi label="Scheduled upcoming" :value="$kpis['scheduled_upcoming']" accent="blue" :href="route('scheduling.index')" />
        <x-kpi label="Failed (24h)" :value="$kpis['failed_24h']" accent="red" :href="route('messages.index', ['status' => 'failed'])" />
        <x-kpi label="Total contacts" :value="$kpis['total_contacts']" :href="route('contacts.index')" />
        <x-kpi label="Active templates" :value="$kpis['active_templates']" :href="route('templates.index')" />
        <x-kpi label="WhatsApp account" value="" :href="route('settings.whatsapp')">
            <x-badge :status="$kpis['account_status']" />
        </x-kpi>
    </div>

    {{-- ── Activity + upcoming ────────────────────────────────────────────── --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Recent activity">
            @if ($recentMessages->isEmpty())
                <p class="text-sm text-gray-500">No messages sent yet.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($recentMessages as $message)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <a href="{{ route('messages.show', $message) }}" class="block truncate text-sm font-medium text-gray-900 hover:text-emerald-600">
                                    {{ $message->recipient_name ?? $message->recipient_phone }}
                                </a>
                                <p class="truncate text-xs text-gray-500">{{ $message->template?->name ?? 'Message' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-badge :status="$message->status" />
                                <span class="text-xs text-gray-400">{{ $message->created_at->diffForHumans(short: true) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Upcoming schedules">
            @if ($upcomingSchedules->isEmpty())
                <div class="flex flex-col items-start gap-2">
                    <p class="text-sm text-gray-500">No upcoming scheduled messages.</p>
                    <a href="{{ route('scheduling.create') }}" class="text-sm font-medium text-emerald-600 hover:underline">Schedule a message →</a>
                </div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($upcomingSchedules as $schedule)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">{{ $schedule->name ?? $schedule->template?->name ?? 'Scheduled message' }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $schedule->template?->name ?? '—' }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-gray-500">
                                {{ $schedule->run_at->setTimezone($workspace->timezone)->format('d M, g:i A') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-app-layout>
