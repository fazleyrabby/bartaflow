{{--
    Floating toast region. Reads Laravel session flash on load and renders any
    runtime toasts pushed to the Alpine 'toasts' store. See docs/frontend.md §2.
--}}
<div
    x-data
    x-init="
        @if (session('success')) $store.toasts.push({ type: 'success', message: @js(session('success')) }); @endif
        @if (session('error')) $store.toasts.push({ type: 'error', message: @js(session('error')) }); @endif
        @if (session('status')) $store.toasts.push({ type: 'info', message: @js(session('status')) }); @endif
    "
    class="pointer-events-none fixed right-4 top-4 z-[60] flex w-full max-w-sm flex-col gap-2"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-transition
            class="pointer-events-auto flex items-start gap-3 rounded-lg border bg-white px-4 py-3 shadow-md"
            :class="{
                'border-emerald-200': toast.type === 'success',
                'border-red-200': toast.type === 'error',
                'border-blue-200': toast.type === 'info',
            }"
        >
            <span
                class="mt-1 h-2 w-2 shrink-0 rounded-full"
                :class="{
                    'bg-emerald-500': toast.type === 'success',
                    'bg-red-500': toast.type === 'error',
                    'bg-blue-500': toast.type === 'info',
                }"
            ></span>
            <p class="flex-1 text-sm text-gray-700" x-text="toast.message"></p>
            <button @click="$store.toasts.remove(toast.id)" class="text-gray-400 hover:text-gray-600" aria-label="Dismiss">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
