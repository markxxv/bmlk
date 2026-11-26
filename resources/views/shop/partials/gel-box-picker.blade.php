<section class="mt-8 rounded-2xl bg-white p-4 sm:p-5" aria-labelledby="gel-box-title">
    <div class="flex items-center justify-between gap-3">
        <h2 id="gel-box-title" class="text-base font-semibold text-zinc-900">{{ __('Composez votre coffret') }}</h2>
        <span class="rounded-full bg-[#F3E4E7] px-3 py-1 text-sm font-semibold tabular-nums text-bmlk-brand" aria-live="polite">
            <span x-text="selectedGels.length"></span> / 6
        </span>
    </div>
    <p class="mt-1 text-sm text-zinc-500">{{ __('Choisissez vos 6 gels') }}. {{ __('Vous pouvez choisir plusieurs fois le même gel.') }}</p>

    <div class="mt-4 grid grid-cols-6 gap-1.5 sm:gap-2">
        <template x-for="slot in 6" :key="slot">
            <div class="relative flex aspect-square items-center justify-center rounded-lg bg-zinc-100">
                <template x-if="selectedGels[slot - 1]">
                    <button type="button" class="relative size-full rounded-lg focus-visible:outline-2 focus-visible:outline-bmlk-brand"
                        @click="selectedGels.splice(slot - 1, 1)"
                        :aria-label="@js(__('Retirer')) + ' ' + selectedGels[slot - 1].name">
                        <template x-if="selectedGels[slot - 1].image">
                            <img :src="selectedGels[slot - 1].image" :alt="selectedGels[slot - 1].name" class="size-full rounded-lg object-cover">
                        </template>
                        <span x-show="!selectedGels[slot - 1].image" class="text-xs text-zinc-500" x-text="slot"></span>
                        <span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-bmlk-brand text-white"><x-lucide-x class="size-3" /></span>
                    </button>
                </template>
                <span x-show="!selectedGels[slot - 1]" class="text-sm font-medium text-zinc-400" x-text="slot"></span>
            </div>
        </template>
    </div>
    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-100" aria-hidden="true">
        <div class="h-full rounded-full bg-bmlk-brand transition-all" :style="'width:' + (selectedGels.length / 6 * 100) + '%'"></div>
    </div>

    <div class="relative mt-5">
        <x-lucide-search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
        <input type="search" x-model="gelSearch" placeholder="{{ __('Rechercher un gel…') }}" aria-label="{{ __('Rechercher un gel…') }}"
            class="w-full rounded-xl bg-zinc-100 py-3 pl-10 pr-3 text-base outline-none focus:ring-2 focus:ring-bmlk-brand">
    </div>
    <p class="mt-3 text-xs text-zinc-500" aria-live="polite"><span x-text="filteredGels.length"></span> {{ __('gels disponibles') }}</p>

    <div class="mt-2 max-h-80 space-y-1 overflow-y-auto overscroll-contain pr-1">
        <template x-for="gel in filteredGels" :key="gel.id">
            <div class="flex items-center gap-3 rounded-xl bg-zinc-50 p-2" :class="gelCount(gel.id) ? 'ring-1 ring-inset ring-bmlk-brand' : ''">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-white">
                    <template x-if="gel.image"><img :src="gel.image" :alt="gel.name" loading="lazy" class="size-full rounded-lg object-contain"></template>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-zinc-900" x-text="gel.name"></p>
                    <p x-show="gel.sku" class="text-xs text-zinc-500" x-text="gel.sku"></p>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button x-show="gelCount(gel.id)" type="button" @click="removeGel(gel.id)" :aria-label="@js(__('Retirer')) + ' ' + gel.name"
                        class="flex size-9 items-center justify-center rounded-full bg-white text-bmlk-brand"><x-lucide-minus class="size-4" /></button>
                    <span x-show="gelCount(gel.id)" class="min-w-4 text-center text-sm font-semibold text-bmlk-brand" x-text="gelCount(gel.id)"></span>
                    <button type="button" @click="addGel(gel)" :disabled="selectedGels.length >= 6" :aria-label="@js(__('Ajouter')) + ' ' + gel.name"
                        class="flex size-9 items-center justify-center rounded-full bg-bmlk-brand text-white disabled:bg-zinc-200 disabled:text-zinc-400"><x-lucide-plus class="size-4" /></button>
                </div>
            </div>
        </template>
        <p x-show="filteredGels.length === 0" class="py-5 text-center text-sm text-zinc-500">{{ __('Aucun gel trouvé.') }}</p>
    </div>
</section>
