@php
    /** @var \App\Models\ScheduledMessage|null $schedule */
    $isEdit = isset($schedule);
    $action = $isEdit ? route('scheduling.update', $schedule) : route('scheduling.store');
    $tz = $workspace->timezone;

    // Prefill (edit)
    $pName = old('name', $isEdit ? $schedule->name : '');
    $pAccount = old('account_id', $isEdit ? $schedule->whatsapp_account_id : ($accounts->first()->id ?? null));
    $pTemplate = old('template_id', $isEdit ? $schedule->template_id : ($templates->first()->id ?? null));
    $pMode = old('recipient_mode', $isEdit
        ? match ($schedule->recipient_type) { 'tag' => 'tag', 'filter' => 'all', default => 'selected' }
        : 'selected');
    $pTagId = old('tag_id', $isEdit ? ($schedule->recipient_payload['tag_id'] ?? null) : null);
    $pContactIds = old('contact_ids', $isEdit ? ($schedule->recipient_payload['contact_ids'] ?? []) : []);
    $pRunAt = old('run_at', $isEdit ? $schedule->run_at->setTimezone($tz)->format('Y-m-d\TH:i') : '');
@endphp

<form
    method="POST"
    action="{{ $action }}"
    x-data="scheduler({
        templates: {{ Illuminate\Support\Js::from($templates) }},
        totalContacts: {{ $contactCount }},
        timezone: @js($tz),
        mode: @js($pMode),
        templateId: {{ $pTemplate ?? 'null' }},
        tagId: {{ $pTagId ?? 'null' }},
        selected: {{ Illuminate\Support\Js::from(array_map('intval', (array) $pContactIds)) }},
        runAt: @js($pRunAt),
    })"
    @submit="submitting = true"
>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- ── Left ── --}}
        <div class="space-y-5">
            <x-card title="Message">
                <div class="space-y-4">
                    <x-form.input name="name" label="Schedule name (optional)" :value="$pName" placeholder="e.g. Friday promo" />

                    <div>
                        <label for="account_id" class="block mb-2 text-sm font-medium text-gray-900">WhatsApp account</label>
                        <select id="account_id" name="account_id" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected($pAccount == $acc->id)>{{ $acc->label }} ({{ $acc->phone_number }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="template_id" class="block mb-2 text-sm font-medium text-gray-900">Template</label>
                        <select id="template_id" name="template_id" x-model.number="templateId" @change="syncTemplate()" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl->id }}" @selected($pTemplate == $tpl->id)>{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-card>

            <x-card title="Recipients">
                <div class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <label class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input type="radio" name="recipient_mode" value="selected" x-model="mode" class="text-emerald-600 focus:ring-emerald-500"> Selected
                        </label>
                        <label class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input type="radio" name="recipient_mode" value="tag" x-model="mode" class="text-emerald-600 focus:ring-emerald-500"> By tag
                        </label>
                        <label class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input type="radio" name="recipient_mode" value="all" x-model="mode" class="text-emerald-600 focus:ring-emerald-500"> All contacts
                        </label>
                    </div>

                    <div x-show="mode === 'selected'" class="space-y-2">
                        <input type="text" x-model="search" placeholder="Search contacts…" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 divide-y divide-gray-100">
                            @foreach ($contacts as $c)
                                <label class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-gray-50" x-show="matches('{{ strtolower($c->name.' '.$c->phone) }}')">
                                    <input type="checkbox" name="contact_ids[]" value="{{ $c->id }}" x-model.number="selected" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-gray-900">{{ $c->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $c->phone }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div x-show="mode === 'tag'">
                        <select name="tag_id" x-model.number="tagId" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Choose a tag…</option>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}" @selected($pTagId == $tag->id)>{{ $tag->name }} ({{ $tag->contacts_count }})</option>
                            @endforeach
                        </select>
                    </div>

                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                        Recipients are resolved when the message runs — opted-out contacts are skipped at send time.
                    </p>
                </div>
            </x-card>
        </div>

        {{-- ── Right ── --}}
        <div class="space-y-5">
            <x-card title="When to send">
                <div class="space-y-4">
                    <div>
                        <label for="run_at" class="block mb-2 text-sm font-medium text-gray-900">Date & time</label>
                        <input type="datetime-local" id="run_at" name="run_at" x-model="runAt"
                            class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <p class="mt-1 text-xs text-gray-500">Times are in your workspace timezone: <span class="font-medium">{{ $tz }}</span></p>
                    </div>

                    <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700" x-show="runAt">
                        Will send on <span class="font-medium" x-text="prettyRunAt()"></span> ({{ $tz }})
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800" x-text="recipientCount"></p>
                        <p class="text-xs text-gray-500">recipients (approx.)</p>
                    </div>
                </div>

                <x-slot:footer>
                    <button type="submit" x-bind:disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ $isEdit ? 'Save schedule' : 'Schedule message' }}
                    </button>
                    <a href="{{ route('scheduling.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                </x-slot:footer>
            </x-card>
        </div>
    </div>
</form>

<script>
function scheduler(config) {
    return {
        templates: config.templates,
        totalContacts: config.totalContacts,
        templateId: config.templateId,
        mode: config.mode,
        selected: config.selected ?? [],
        tagId: config.tagId,
        runAt: config.runAt,
        search: '',
        submitting: false,
        sampleBody: '',

        init() { this.syncTemplate(); },

        syncTemplate() {
            const tpl = this.templates.find(t => t.id === this.templateId);
            this.sampleBody = tpl ? tpl.body : '';
        },

        matches(haystack) {
            if (!this.search) return true;
            return haystack.includes(this.search.toLowerCase());
        },

        prettyRunAt() {
            if (!this.runAt) return '';
            try {
                return new Date(this.runAt).toLocaleString(undefined, {
                    weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
                    hour: 'numeric', minute: '2-digit',
                });
            } catch (e) { return this.runAt; }
        },

        get recipientCount() {
            if (this.mode === 'selected') return this.selected.length;
            if (this.mode === 'all') return this.totalContacts;
            return this.tagId ? '—' : 0;
        },
    };
}
</script>
