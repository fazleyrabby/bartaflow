<div
    x-data
    x-init="
        @if (session('success')) $store.toasts.push({ type: 'success', message: @js(session('success')) }); @endif
        @if (session('error')) $store.toasts.push({ type: 'error', message: @js(session('error')) }); @endif
        @if (session('status')) $store.toasts.push({ type: 'info', message: @js(session('status')) }); @endif
    "
    class="fixed top-4 right-4 z-[60] flex w-full max-w-sm flex-col gap-2"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-transition
            class="flex w-full items-center gap-3 rounded-lg border bg-white px-4 py-3 shadow-lg"
            :class="{
                'border-green-200': toast.type === 'success',
                'border-red-200': toast.type === 'error',
                'border-blue-200': toast.type === 'info',
            }"
        >
            <span
                class="inline-flex items-center justify-center h-8 w-8 rounded-full shrink-0"
                :class="{
                    'bg-green-100 text-green-600': toast.type === 'success',
                    'bg-red-100 text-red-600': toast.type === 'error',
                    'bg-blue-100 text-blue-600': toast.type === 'info',
                }"
            >
                <svg x-show="toast.type === 'success'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <svg x-show="toast.type === 'error'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <svg x-show="toast.type === 'info'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <p class="flex-1 text-sm font-medium text-gray-800" x-text="toast.message"></p>
            <button @click="$store.toasts.remove(toast.id)" class="text-gray-400 hover:text-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
