<x-app-layout>
    <x-slot:title>New Message</x-slot:title>
    <x-slot:header>New Message</x-slot:header>
    <x-slot:subheader>Send a template to one or many contacts. Opted-out contacts are skipped automatically.</x-slot:subheader>

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($accounts->isEmpty())
        {{-- Blocked: no connected account --}}
        <x-empty-state
            title="Connect a WhatsApp account first"
            message="You need at least one connected WhatsApp account before you can send messages.">
            <x-slot:actions>
                <x-button :href="route('settings.whatsapp')">Go to WhatsApp settings</x-button>
            </x-slot:actions>
        </x-empty-state>
    @elseif ($templates->isEmpty())
        {{-- Blocked: no active template --}}
        <x-empty-state
            title="Create a template first"
            message="Messages are sent from reusable templates. Create one to get started.">
            <x-slot:actions>
                <x-button :href="route('templates.create')">New template</x-button>
            </x-slot:actions>
        </x-empty-state>
    @else
        <form
            method="POST"
            action="{{ route('messages.store') }}"
            x-data="composer({
                templates: {{ Illuminate\Support\Js::from($templates) }},
                totalContacts: {{ $contactCount }},
            })"
            @submit="submitting = true"
        >
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- ── Left: compose ── --}}
                <div class="space-y-5">
                    <x-card title="Account & template">
                        <div class="space-y-4">
                            <div>
                                <label for="account_id" class="block mb-2 text-sm font-medium text-gray-900">WhatsApp account</label>
                                <select id="account_id" name="account_id" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->label }} ({{ $acc->phone_number }}){{ $acc->is_default ? ' · default' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="template_id" class="block mb-2 text-sm font-medium text-gray-900">Template</label>
                                <select id="template_id" name="template_id" x-model.number="templateId" @change="syncTemplate()" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach ($templates as $tpl)
                                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <template x-if="requiredVars.length > 0">
                                <div class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">
                                    Variables: <span class="font-medium" x-text="requiredVars.join(', ')"></span>
                                </div>
                            </template>
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

                            {{-- Selected contacts --}}
                            <div x-show="mode === 'selected'" class="space-y-2">
                                <input type="text" x-model="search" placeholder="Search contacts…" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <div class="max-h-56 overflow-y-auto rounded-lg border border-gray-200 divide-y divide-gray-100">
                                    @foreach ($contacts as $c)
                                        <label class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-gray-50"
                                               x-show="matches('{{ strtolower($c->name.' '.$c->phone) }}')">
                                            <input type="checkbox" name="contact_ids[]" value="{{ $c->id }}" x-model="selected" class="rounded text-emerald-600 focus:ring-emerald-500">
                                            <span class="font-medium text-gray-900">{{ $c->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $c->phone }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- By tag --}}
                            <div x-show="mode === 'tag'">
                                <select name="tag_id" x-model.number="tagId" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">Choose a tag…</option>
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}">{{ $tag->name }} ({{ $tag->contacts_count }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                Opted-out contacts are skipped automatically and never messaged.
                            </p>
                        </div>
                    </x-card>
                </div>

                {{-- ── Right: review ── --}}
                <div class="space-y-5">
                    <x-card title="Review & send">
                        <div class="space-y-4">
                            <div class="rounded-lg bg-emerald-50 p-4 text-center">
                                <p class="text-3xl font-bold text-emerald-700" x-text="recipientCount"></p>
                                <p class="text-sm text-emerald-600" x-text="recipientCount === 1 ? 'recipient' : 'recipients'"></p>
                            </div>

                            <div>
                                <p class="mb-1.5 text-xs font-medium text-gray-500">Sample preview</p>
                                <div class="min-h-[6rem] whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-sm text-gray-800" x-text="sampleBody"></div>
                            </div>
                        </div>

                        <x-slot:footer>
                            <button type="submit" x-bind:disabled="submitting || recipientCount === 0"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!submitting">Send now</span>
                                <span x-show="submitting" x-cloak>Queuing…</span>
                            </button>
                            <a href="{{ route('messages.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                        </x-slot:footer>
                    </x-card>
                </div>
            </div>
        </form>
    @endif
</x-app-layout>

<script>
function composer(config) {
    return {
        templates: config.templates,
        totalContacts: config.totalContacts,
        templateId: config.templates[0]?.id ?? null,
        requiredVars: [],
        sampleBody: '',
        mode: 'selected',
        selected: [],
        tagId: null,
        search: '',
        submitting: false,

        init() {
            this.syncTemplate();
        },

        syncTemplate() {
            const tpl = this.templates.find(t => t.id === this.templateId);
            this.requiredVars = (tpl && tpl.variables) ? tpl.variables : [];
            this.sampleBody = tpl ? tpl.body : '';
        },

        matches(haystack) {
            if (!this.search) return true;
            return haystack.includes(this.search.toLowerCase());
        },

        get recipientCount() {
            if (this.mode === 'selected') return this.selected.length;
            if (this.mode === 'all') return this.totalContacts;
            return this.tagId ? '—' : 0;
        },
    };
}
</script>
