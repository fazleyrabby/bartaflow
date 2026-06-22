<div class="space-y-4">
    <x-form.input name="name" label="Name" type="text" placeholder="John Doe" required x-model="name" />

    <x-form.input name="phone" label="Phone" type="text" placeholder="+8801712345678" required x-model="phone" />

    <x-form.input name="email" label="Email" type="email" placeholder="john@example.com" x-model="email" />

    <div class="space-y-1">
        <label class="block text-base font-medium text-gray-700">Tags</label>
        <div class="rounded-lg border border-gray-300 p-2" x-data="{ search: '' }">
            <input type="text" x-model="search" placeholder="Search tags…" class="mb-2 w-full border-0 border-b border-gray-200 px-0 py-1 text-sm focus:border-emerald-500 focus:ring-0">
            <div class="flex flex-wrap gap-1.5">
                <template x-for="tag in availableTags.filter(t => !search || t.name.toLowerCase().includes(search.toLowerCase()))" :key="tag.id">
                    <button type="button"
                        @click="toggleTag(tag.id)"
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium transition"
                        :style="tags.includes(tag.id) ? 'background-color: ' + tag.color + '; color: white;' : 'background-color: ' + tag.color + '20; color: ' + tag.color"
                    >
                        <span x-text="tag.name"></span>
                    </button>
                </template>
                <template x-if="filteredTags.length === 0">
                    <span class="text-xs text-gray-400">No tags found</span>
                </template>
            </div>
        </div>
        <template x-for="tagId in tags" :key="tagId">
            <input type="hidden" name="tags[]" :value="tagId">
        </template>
    </div>

    <div class="space-y-1">
        <label class="block text-base font-medium text-gray-700">Custom Fields</label>
        <div x-data="{ fields: [] }">
            <template x-for="(field, index) in fields" :key="index">
                <div class="mb-2 flex items-center gap-2">
                    <input type="text" x-model="fields[index].key" placeholder="Key" class="block w-1/3 rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <input type="text" x-model="fields[index].value" placeholder="Value" class="block flex-1 rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <button type="button" @click="fields.splice(index, 1)" class="text-red-500 hover:text-red-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
            <button type="button" @click="fields.push({ key: '', value: '' })" class="text-sm text-emerald-600 hover:underline">+ Add field</button>
            <template x-for="(field, index) in fields" :key="index">
                <input type="hidden" :name="'custom_fields[' + field.key + ']'" :value="field.value">
            </template>
        </div>
    </div>

    <div class="space-y-1">
        <label for="notes" class="block text-base font-medium text-gray-700">Notes</label>
        <textarea id="notes" name="notes" x-model="notes" rows="3" placeholder="Any notes about this contact…" class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
    </div>
</div>
