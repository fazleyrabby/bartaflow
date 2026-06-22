@php $lb = '{{'; $rb = '}}'; @endphp
<x-app-layout>
    <x-slot:title>Templates</x-slot:title>
    <x-slot:header>Templates</x-slot:header>
    <x-slot:subheader>Reusable messages with {{ $lb }}variables{{ $rb }} for fast, consistent sending.</x-slot:subheader>

    <x-slot:actions>
        <x-button :href="route('templates.create')">New template</x-button>
    </x-slot:actions>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="mb-5 flex flex-wrap items-center gap-3">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search templates…"
            class="block w-full max-w-xs rounded-lg border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
        >

        <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="archived" @selected(request('status') === 'archived')>Archived</option>
        </select>

        @if (request()->anyFilled(['search', 'category', 'status']))
            <a href="{{ route('templates.index') }}" class="text-sm text-emerald-600 hover:underline">Clear</a>
        @endif
    </form>

    {{-- Category tabs --}}
    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('templates.index', array_filter(['search' => request('search'), 'status' => request('status')])) }}"
           @class([
               'rounded-full px-3 py-1.5 text-sm font-medium',
               'bg-gray-900 text-white' => ! request('category'),
               'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50' => request('category'),
           ])>All</a>
        @foreach ($categories as $cat)
            <a href="{{ route('templates.index', array_filter(['category' => $cat->value, 'search' => request('search'), 'status' => request('status')])) }}"
               @class([
                   'rounded-full px-3 py-1.5 text-sm font-medium',
                   'bg-gray-900 text-white' => request('category') === $cat->value,
                   'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50' => request('category') !== $cat->value,
               ])>{{ $cat->label() }}</a>
        @endforeach
    </div>

    @if ($templates->count() > 0)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($templates as $template)
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-gray-900">{{ $template->name }}</h3>
                        <x-badge :status="$template->status" />
                    </div>

                    <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-400">{{ $template->category->label() }}</p>

                    <p class="mb-4 line-clamp-3 flex-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($template->body, 140) }}</p>

                    @if (! empty($template->variables))
                        <div class="mb-4 flex flex-wrap gap-1.5">
                            @foreach (array_slice($template->variables, 0, 5) as $var)
                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $lb.$var.$rb }}</span>
                            @endforeach
                            @if (count($template->variables) > 5)
                                <span class="text-xs text-gray-400">+{{ count($template->variables) - 5 }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="mt-auto flex items-center gap-2 border-t border-gray-100 pt-3">
                        <a href="{{ route('templates.edit', $template) }}" class="text-sm font-medium text-emerald-600 hover:underline">Edit</a>

                        <form method="POST" action="{{ route('templates.duplicate', $template) }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700">Duplicate</button>
                        </form>

                        <form method="POST" action="{{ route('templates.destroy', $template) }}"
                              class="ml-auto"
                              onsubmit="return confirm('Delete “{{ $template->name }}”? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-700">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $templates->links() }}</div>
    @else
        <x-empty-state
            title="No templates yet"
            :message="'Create reusable messages with '.$lb.'name'.$rb.', '.$lb.'order_id'.$rb.' and more — then send them in seconds.'">
            <x-slot:actions>
                <x-button :href="route('templates.create')">New template</x-button>
            </x-slot:actions>
        </x-empty-state>
    @endif
</x-app-layout>
