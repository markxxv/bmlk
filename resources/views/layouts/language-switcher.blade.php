@php
    $switcherUrls = \App\Support\LocaleUrls::all();
    $names = ['fr' => 'Français', 'en' => 'English', 'es' => 'Español'];
    $current = app()->getLocale();
@endphp

@if (count($switcherUrls) > 1)
    @if (($variant ?? 'inline') === 'dropdown')
        <div
            x-data="{
                open: false,
                toggle() { this.open ? this.close() : this.show(); },
                show() { this.open = true; this.$nextTick(() => this.items()[Math.max(0, this.items().findIndex(i => i.hasAttribute('aria-current')))]?.focus()); },
                close(focusButton = false) { this.open = false; if (focusButton) this.$refs.button.focus(); },
                items() { return Array.from(this.$refs.menu.querySelectorAll('[role=menuitem]')); },
                move(step) {
                    const list = this.items();
                    const next = (list.indexOf(document.activeElement) + step + list.length) % list.length;
                    list[next].focus();
                },
            }"
            @keydown.escape.prevent.stop="close(true)"
            @keydown.arrow-down.prevent="open ? move(1) : show()"
            @keydown.arrow-up.prevent="open ? move(-1) : show()"
            @keydown.home.prevent="open && items()[0].focus()"
            @keydown.end.prevent="open && items().at(-1).focus()"
            @focusout="if (open && !$el.contains($event.relatedTarget)) close()"
            @click.outside="close()"
            class="relative {{ $class ?? '' }}"
        >
            <button
                type="button"
                x-ref="button"
                @click="toggle()"
                :aria-expanded="open.toString()"
                aria-haspopup="menu"
                aria-controls="language-menu"
                aria-label="{{ __('Langue') }} : {{ $names[$current] ?? strtoupper($current) }}"
                class="inline-flex h-11 items-center gap-1.5 rounded-full px-3 text-xs font-semibold uppercase tracking-widest text-zinc-800 transition hover:text-bmlk-brand focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bmlk-brand"
                :class="open && 'text-bmlk-brand'"
            >
                <x-lucide-globe class="h-4 w-4" />
                <span>{{ strtoupper($current) }}</span>
                <x-lucide-chevron-down class="h-3.5 w-3.5 transition-transform duration-200" ::class="open && 'rotate-180'" />
            </button>

            <div
                id="language-menu"
                x-ref="menu"
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                role="menu"
                aria-label="{{ __('Langue') }}"
                class="absolute right-0 top-full z-50 mt-1 min-w-44 origin-top-right overflow-hidden rounded-2xl bg-white p-1.5 shadow-xl ring-1 ring-black/5"
            >
                @foreach ($switcherUrls as $code => $url)
                    <a href="{{ $url }}"
                       role="menuitem"
                       hreflang="{{ $code }}"
                       lang="{{ $code }}"
                       @if ($code === $current) aria-current="true" @endif
                       tabindex="-1"
                       class="flex items-center justify-between gap-4 rounded-xl px-3 py-2.5 text-sm transition focus:outline-none {{ $code === $current ? 'bg-bmlk-brand/10 font-semibold text-bmlk-brand' : 'text-zinc-700 hover:bg-zinc-100 focus:bg-zinc-100' }}">
                        <span>{{ $names[$code] ?? strtoupper($code) }}</span>
                        @if ($code === $current)
                            <x-lucide-check class="h-4 w-4" />
                        @else
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">{{ $code }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <nav aria-label="{{ __('Langue') }}" class="flex items-center gap-1 text-xs font-semibold uppercase tracking-widest {{ $class ?? '' }}">
            @foreach ($switcherUrls as $code => $url)
                <a href="{{ $url }}"
                   hreflang="{{ $code }}"
                   lang="{{ $code }}"
                   @if ($code === $current) aria-current="true" @endif
                   class="rounded-full px-2 py-1 transition {{ $code === $current ? 'bg-bmlk-brand text-white' : 'text-zinc-700 hover:text-bmlk-brand' }}">
                    {{ strtoupper($code) }}
                </a>
            @endforeach
        </nav>
    @endif
@endif
