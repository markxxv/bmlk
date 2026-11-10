<x-layout :title="__('Mes formations | BLACK MILK')" robots="noindex, nofollow">
    <main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">
        @include('account.partials.nav')

        <h1 class="mb-6 font-serif text-3xl tracking-tight text-zinc-900 sm:text-4xl">{{ __('Mes formations') }}</h1>

        @forelse ($purchases as $purchase)
            @php
                $course = $purchase->course;
                $pdfUrl = $course->infoPdfUrl();
            @endphp
            <article class="mb-5 grid gap-6 rounded-3xl bg-white p-6 sm:p-8 lg:grid-cols-12" id="course-{{ $course->slug }}">
                <div class="lg:col-span-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-bmlk-brand">{{ $course->tag }}</p>
                    <h2 class="mt-3 font-serif text-3xl font-medium leading-tight text-zinc-900">{{ $course->name }}</h2>
                    <p class="mt-3 text-sm leading-6 text-zinc-600">{{ $course->description }}</p>
                    <p class="mt-4 text-xs text-zinc-400">{{ __('Acheté le :date', ['date' => $purchase->paid_at?->translatedFormat('d F Y')]) }}</p>
                </div>

                <div class="grid content-start gap-3 sm:grid-cols-2 lg:col-span-7">
                    @if ($course->telegram_channel_url)
                        <a href="{{ $course->telegram_channel_url }}" target="_blank" rel="noopener noreferrer"
                            class="group flex items-start gap-4 rounded-2xl bg-bmlk-page p-5 transition hover:bg-bmlk-soft">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-bmlk-brand"><x-lucide-graduation-cap class="h-5 w-5" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-zinc-900">{{ __('Supports de formation') }}</span>
                                <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('Groupe Telegram avec les cours et les matériaux.') }}</span>
                            </span>
                            <x-lucide-arrow-up-right class="h-4 w-4 shrink-0 text-bmlk-brand transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </a>
                    @endif

                    @if ($course->telegram_chat_url)
                        <a href="{{ $course->telegram_chat_url }}" target="_blank" rel="noopener noreferrer"
                            class="group flex items-start gap-4 rounded-2xl bg-bmlk-page p-5 transition hover:bg-bmlk-soft">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-bmlk-brand"><x-lucide-messages-square class="h-5 w-5" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-zinc-900">{{ __('Chat de la formation') }}</span>
                                <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('Échangez avec les autres participantes.') }}</span>
                            </span>
                            <x-lucide-arrow-up-right class="h-4 w-4 shrink-0 text-bmlk-brand transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </a>
                    @endif

                    @if ($pdfUrl)
                        <a href="{{ $pdfUrl }}" target="_blank" rel="noopener"
                            class="group flex items-start gap-4 rounded-2xl bg-bmlk-page p-5 transition hover:bg-bmlk-soft">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-bmlk-brand"><x-lucide-file-text class="h-5 w-5" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-zinc-900">{{ __('Fiche de la formation (PDF)') }}</span>
                                <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('Toutes les informations sur le programme.') }}</span>
                            </span>
                            <x-lucide-download class="h-4 w-4 shrink-0 text-bmlk-brand" />
                        </a>
                    @endif

                    @if (! $course->telegram_channel_url && ! $course->telegram_chat_url)
                        <p class="rounded-2xl bg-bmlk-page p-5 text-sm leading-6 text-zinc-600 sm:col-span-2">
                            {{ __('Les liens d’accès à votre formation arrivent très bientôt. Écrivez-nous si vous ne les recevez pas sous 24 h : office.blackmilk@gmail.com.') }}
                        </p>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl bg-white p-8 text-center sm:p-12">
                <x-lucide-graduation-cap class="mx-auto h-8 w-8 text-bmlk-brand" stroke-width="1.4" />
                <p class="mt-4 text-sm text-zinc-600">{{ __('Vous n’avez pas encore de formation.') }}</p>
                <a href="{{ route('formations') }}" class="mt-6 inline-flex h-12 items-center justify-center rounded-full bg-bmlk-brand px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763]">
                    {{ __('Voir les formations') }}
                </a>
            </div>
        @endforelse
    </main>
</x-layout>
