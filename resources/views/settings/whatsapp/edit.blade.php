<x-app-layout>
    <x-slot:title>Edit WhatsApp Account</x-slot:title>
    <x-slot:header>Edit WhatsApp Account</x-slot:header>
    <x-slot:subheader>Update account details. Leave the token blank to keep the existing one.</x-slot:subheader>

    <div class="max-w-2xl">
        <x-card title="Account credentials">
            <form method="POST" action="{{ route('settings.whatsapp.update', $account) }}" x-data="{ showToken: false }">
                @csrf
                @method('PATCH')

                <div class="space-y-4">
                    <x-form.input name="label" label="Account label" :value="old('label', $account->label)" required />

                    <x-form.input name="phone_number" label="Phone number (E.164)" :value="old('phone_number', $account->phone_number)" required />

                    <x-form.input name="phone_number_id" label="Phone number ID" :value="old('phone_number_id', $account->phone_number_id)" required />

                    <x-form.input name="business_account_id" label="WhatsApp Business Account ID" :value="old('business_account_id', $account->business_account_id)" required />

                    <div class="space-y-1">
                        <label for="access_token" class="block text-sm font-medium text-gray-700">
                            Access token
                            <span class="ml-1 text-xs font-normal text-gray-500">(leave blank to keep existing)</span>
                        </label>
                        <div class="flex gap-2">
                            <input
                                :type="showToken ? 'text' : 'password'"
                                id="access_token"
                                name="access_token"
                                class="block flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 {{ $errors->has('access_token') ? 'border-red-400' : '' }}"
                                autocomplete="off"
                                placeholder="••••••••••••••••"
                            />
                            <button type="button" @click="showToken = !showToken"
                                class="rounded-lg border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">
                                <span x-show="!showToken">Show</span>
                                <span x-show="showToken">Hide</span>
                            </button>
                        </div>
                        @error('access_token')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <x-button type="submit">Save changes</x-button>
                    <a href="{{ route('settings.whatsapp') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
