<x-app-layout>
    <x-slot:title>Connect WhatsApp Account</x-slot:title>
    <x-slot:header>Connect WhatsApp Account</x-slot:header>
    <x-slot:subheader>Enter your WhatsApp Cloud API credentials.</x-slot:subheader>

    <div class="w-full max-w-3xl space-y-4">
        <x-card title="Account credentials">
            <form method="POST" action="{{ route('settings.whatsapp.store') }}" x-data="{ showToken: false }">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="label" label="Account label" :value="old('label')" placeholder="e.g. Customer Support" required />

                    <x-form.input name="phone_number" label="Phone number (E.164)" :value="old('phone_number')" placeholder="+8801700000000" required />

                    <x-form.input name="phone_number_id" label="Phone number ID" :value="old('phone_number_id')" placeholder="From Meta Developer Portal" required />

                    <x-form.input name="business_account_id" label="WhatsApp Business Account ID" :value="old('business_account_id')" required />

                    <div class="sm:col-span-2">
                        <label for="access_token" class="block mb-2 text-sm font-medium text-gray-900">
                            Access token
                        </label>
                        <div class="flex gap-2">
                            <input
                                :type="showToken ? 'text' : 'password'"
                                id="access_token"
                                name="access_token"
                                class="block flex-1 rounded-lg border-gray-300 bg-gray-50 p-2.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 {{ $errors->has('access_token') ? 'border-red-400' : '' }}"
                                autocomplete="off"
                                required
                            />
                            <button type="button" @click="showToken = !showToken"
                                class="rounded-lg border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">
                                <span x-show="!showToken">Show</span>
                                <span x-show="showToken">Hide</span>
                            </button>
                        </div>
                        @error('access_token')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <x-button type="submit">Connect account</x-button>
                    <a href="{{ route('settings.whatsapp') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </x-card>

        <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700 border border-blue-200">
            <strong>Where to find your credentials:</strong> Go to
            <strong>Meta Developer Portal → Your App → WhatsApp → API Setup</strong>.
            You'll find the Phone Number ID, WABA ID, and can generate a temporary access token.
        </div>
    </div>
</x-app-layout>
