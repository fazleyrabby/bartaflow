<x-app-layout>
    <x-slot:title>Contacts</x-slot:title>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Contacts</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your contacts and their tags.</p>
        </div>
        <div class="flex items-center gap-3">
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'import-modal')"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Import CSV
            </button>
            <button
                type="button"
                x-data
                @click="$dispatch('open-modal', 'add-contact-modal')"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
            >
                Add Contact
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-base text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1">
            <input
                type="text"
                placeholder="Search name, phone, email…"
                value="{{ request('search') }}"
                onchange="window.location = '{{ route('contacts.index') }}?search=' + encodeURIComponent(this.value)"
                class="block w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >
        </div>

        <select
            onchange="window.location = this.value ? '{{ route('contacts.index') }}?tag=' + this.value : '{{ route('contacts.index') }}'"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
        >
            <option value="">All tags</option>
            @foreach ($tags as $tag)
                <option value="{{ $tag->id }}" @selected(request('tag') == $tag->id)>{{ $tag->name }}</option>
            @endforeach
        </select>

        <select
            onchange="window.location = this.value !== '' ? '{{ route('contacts.index') }}?opted_out=' + this.value : '{{ route('contacts.index') }}'"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
        >
            <option value="">All status</option>
            <option value="0" @selected(request('opted_out') === '0')>Active</option>
            <option value="1" @selected(request('opted_out') === '1')>Opted out</option>
        </select>

        @if (request()->anyFilled(['search', 'tag', 'opted_out']))
            <a href="{{ route('contacts.index') }}" class="text-sm text-emerald-600 hover:underline">Clear filters</a>
        @endif
    </div>

    {{-- Contact List --}}
    @if ($contacts->count() > 0)
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Phone</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 md:table-cell">Email</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 sm:table-cell">Tags</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($contacts as $contact)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $contact->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $contact->phone }}</td>
                            <td class="hidden px-4 py-3 text-sm text-gray-600 md:table-cell">{{ $contact->email ?? '—' }}</td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($contact->tags as $tag)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                            {{ $tag->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($contact->is_opted_out)
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">Opted out</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        x-data
                                        @click="
                                            $dispatch('open-modal', 'edit-contact-modal');
                                            $dispatch('set-edit-contact', {
                                                id: {{ $contact->id }},
                                                name: '{{ addslashes($contact->name) }}',
                                                phone: '{{ addslashes($contact->phone) }}',
                                                email: '{{ addslashes($contact->email ?? '') }}',
                                                notes: '{{ addslashes($contact->notes ?? '') }}',
                                                tags: {{ json_encode($contact->tags->pluck('id')) }},
                                            })
                                        "
                                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <form method="POST" action="{{ route('contacts.toggle-opt-out', $contact) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="Toggle opt-out">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('contacts.destroy', $contact) }}" class="inline" onsubmit="return confirm('Delete this contact?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-red-500">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $contacts->links() }}
        </div>
    @else
        <x-empty-state title="No contacts yet" message="Add your first contact or import a CSV to get started.">
            <x-slot:actions>
                <button type="button" x-data @click="$dispatch('open-modal', 'add-contact-modal')" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Add Contact
                </button>
            </x-slot:actions>
        </x-empty-state>
    @endif

    {{-- Add Contact Modal --}}
    <x-modal name="add-contact-modal" title="Add Contact">
        <form method="POST" action="{{ route('contacts.store') }}">
            @csrf
            <div class="space-y-4">
                <x-form.input name="name" label="Name" type="text" placeholder="John Doe" required />
                <x-form.input name="phone" label="Phone" type="text" placeholder="+8801712345678" required />
                <x-form.input name="email" label="Email" type="email" placeholder="john@example.com" />
                <x-form.textarea name="notes" label="Notes" placeholder="Any notes about this contact…" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'add-contact-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <x-button type="submit">Save Contact</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Contact Modal --}}
    <x-modal name="edit-contact-modal" title="Edit Contact">
        <form method="POST" x-ref="editForm" action="" x-data="editContactForm()" @set-edit-contact.window="
            action = '/contacts/' + $event.detail.id + '?_method=PATCH';
            $refs.editForm.querySelector('[name=name]').value = $event.detail.name;
            $refs.editForm.querySelector('[name=phone]').value = $event.detail.phone;
            $refs.editForm.querySelector('[name=email]').value = $event.detail.email;
            $refs.editForm.querySelector('[name=notes]').value = $event.detail.notes;
        ">
            @csrf
            <div class="space-y-4">
                <x-form.input name="name" label="Name" type="text" placeholder="John Doe" required />
                <x-form.input name="phone" label="Phone" type="text" placeholder="+8801712345678" required />
                <x-form.input name="email" label="Email" type="email" placeholder="john@example.com" />
                <x-form.textarea name="notes" label="Notes" placeholder="Any notes about this contact…" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'edit-contact-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <x-button type="submit">Update Contact</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Import CSV Modal --}}
    <x-modal name="import-modal" title="Import Contacts">
        <form method="POST" action="{{ route('contacts.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <p class="text-sm text-gray-600">Upload a CSV file with columns: <code>name</code>, <code>phone</code> (required), <code>email</code>, <code>notes</code>.</p>
                <input type="file" name="file" accept=".csv,.txt" required class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100">
                <p class="text-xs text-gray-500">Max {{ config('bartaflow.csv.max_rows') }} rows, {{ config('bartaflow.csv.max_size_kb') / 1024 }}MB.</p>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'import-modal')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <x-button type="submit">Upload & Import</x-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
