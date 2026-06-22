@php
    /** @var \App\Models\Template|null $template */
    $lb = '{{'; $rb = '}}';
    $isEdit = isset($template);
    $action = $isEdit ? route('templates.update', $template) : route('templates.store');
    $bodyValue = old('body', $isEdit ? $template->body : "Hi {{ name }},\n\nyour order {{ order_id }} is confirmed. Thanks for choosing {{ business_name }}!");
@endphp

<div
    x-data="templateEditor({
        previewUrl: '{{ route('templates.preview') }}',
        csrf: '{{ csrf_token() }}',
        body: @js($bodyValue),
    })"
    class="grid grid-cols-1 gap-6 lg:grid-cols-2"
>
    {{-- ── Left: form ── --}}
    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-card title="Template details">
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input
                        name="name"
                        label="Name"
                        :value="old('name', $isEdit ? $template->name : '')"
                        required
                    />

                    <div>
                        <label for="category" class="block mb-2 text-sm font-medium text-gray-900">Category</label>
                        <select id="category" name="category" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->value }}" @selected(old('category', $isEdit ? $template->category->value : 'general') === $cat->value)>{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input
                        name="language"
                        label="Language"
                        :value="old('language', $isEdit ? $template->language : 'en')"
                    />

                    <div>
                        <label for="status" class="block mb-2 text-sm font-medium text-gray-900">Status</label>
                        <select id="status" name="status" class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach ($statuses as $st)
                                <option value="{{ $st->value }}" @selected(old('status', $isEdit ? $template->status->value : 'active') === $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Insert-variable buttons --}}
                <div>
                    <p class="mb-1.5 text-xs font-medium text-gray-500">Insert variable</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (['name', 'phone', 'email', 'order_id', 'business_name', 'today'] as $v)
                            <button type="button" @click="insert('{{ $v }}')"
                                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                {{ $lb.$v.$rb }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="body" class="block mb-2 text-sm font-medium text-gray-900">Body</label>
                    <textarea
                        id="body"
                        name="body"
                        x-ref="body"
                        x-model="body"
                        @input.debounce.400ms="refresh()"
                        rows="8"
                        maxlength="4096"
                        class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 font-mono text-sm focus:border-emerald-500 focus:ring-emerald-500 @error('body') border-red-500 @enderror"
                        required
                    ></textarea>
                    <div class="mt-1 flex justify-between text-xs text-gray-400">
                        <span x-text="body.length + ' / 4096'"></span>
                    </div>
                    @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <x-slot:footer>
                <div class="flex items-center gap-3">
                    <x-button type="submit">{{ $isEdit ? 'Save changes' : 'Create template' }}</x-button>
                    <a href="{{ route('templates.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                </div>
            </x-slot:footer>
        </x-card>
    </form>

    {{-- ── Right: live preview ── --}}
    <div class="space-y-5">
        <x-card title="Live preview">
            <div class="mb-3">
                <label for="preview_contact" class="block mb-1.5 text-xs font-medium text-gray-500">Preview with contact</label>
                <select id="preview_contact" x-model="contactId" @change="refresh()"
                    class="block w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Sample data</option>
                    @foreach ($contacts as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div class="min-h-[8rem] whitespace-pre-wrap rounded-lg bg-emerald-50 p-4 text-sm text-gray-800" x-text="preview || 'Start typing to see a preview…'"></div>

            <template x-if="missing.length > 0">
                <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    Missing values: <span class="font-medium" x-text="missing.join(', ')"></span>
                </div>
            </template>
        </x-card>

        <x-card title="Detected variables">
            <template x-if="variables.length === 0">
                <p class="text-sm text-gray-400">No variables yet. Use {{ $lb }}name{{ $rb }} syntax in the body.</p>
            </template>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="v in variables" :key="v">
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700" x-text="'{' + '{' + v + '}' + '}'"></span>
                </template>
            </div>
        </x-card>
    </div>
</div>

<script>
function templateEditor(config) {
    return {
        body: config.body,
        contactId: '',
        preview: '',
        missing: [],
        variables: [],

        init() {
            this.detect();
            this.refresh();
        },

        // Client-side variable detection (instant chips).
        detect() {
            const re = /\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z0-9_]+)*)\s*\}\}/g;
            const found = [];
            let m;
            while ((m = re.exec(this.body)) !== null) {
                if (!found.includes(m[1])) found.push(m[1]);
            }
            this.variables = found;
        },

        // Insert a {var} token at the cursor.
        insert(name) {
            const el = this.$refs.body;
            const token = '{' + '{' + name + '}' + '}';
            const start = el.selectionStart ?? this.body.length;
            const end = el.selectionEnd ?? this.body.length;
            this.body = this.body.slice(0, start) + token + this.body.slice(end);
            this.$nextTick(() => {
                el.focus();
                const pos = start + token.length;
                el.setSelectionRange(pos, pos);
            });
            this.refresh();
        },

        // Server render for accurate globals + real contact resolution.
        async refresh() {
            this.detect();
            try {
                const res = await fetch(config.previewUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ body: this.body, contact_id: this.contactId || null }),
                });
                if (!res.ok) return;
                const data = await res.json();
                this.preview = data.text;
                this.missing = data.missing;
                this.variables = data.variables;
            } catch (e) { /* keep last good preview */ }
        },
    };
}
</script>
