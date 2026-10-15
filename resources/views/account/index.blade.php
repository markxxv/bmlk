<x-layout :title="__('Mes commandes | BLACK MILK')" robots="noindex, nofollow">
    <main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">
        @include('account.partials.nav')

        @if (blank($user->password))
            <section class="mb-7 grid gap-8 rounded-3xl bg-white p-6 sm:p-9 lg:grid-cols-2 lg:gap-12 lg:p-10">
                <div class="min-w-0">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-bmlk-soft text-bmlk-brand">
                        <x-lucide-shield-check class="h-6 w-6" />
                    </span>

                    <p class="mt-6 text-xs font-medium text-bmlk-brand">{{ __('Votre compte est presque prêt') }}</p>
                    <h1 class="mt-2 font-serif text-3xl leading-tight tracking-tight text-zinc-900 sm:text-4xl">
                        {{ __('Protégez votre compte') }}
                    </h1>
                    <p class="mt-4 max-w-md text-sm leading-7 text-zinc-500">
                        {{ __('Définissez votre mot de passe maintenant pour retrouver vos commandes et votre espace personnel sur tous vos appareils.') }}
                    </p>

                    @if ($currentOrder)
                        <a href="{{ route('account.order', $currentOrder) }}"
                            class="mt-8 flex items-center gap-4 rounded-2xl bg-bmlk-page p-4 transition hover:bg-bmlk-soft">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-bmlk-brand">
                                <x-lucide-package-check class="h-5 w-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs text-zinc-500">{{ __('Votre commande est déjà disponible') }}</span>
                                <span class="mt-1 block text-sm font-semibold text-zinc-900">{{ $currentOrder->order_number }}</span>
                            </span>
                            <x-lucide-arrow-up-right class="h-4 w-4 shrink-0 text-bmlk-brand" />
                        </a>
                    @endif

                    @unless ($user->hasVerifiedEmail())
                        <p class="mt-6 text-xs leading-6 text-zinc-500">
                            {{ __('Votre commande reste accessible ici avant la confirmation de votre adresse e-mail.') }}
                        </p>
                        <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 text-xs font-medium text-bmlk-brand hover:underline">
                                <x-lucide-mail-check class="h-3.5 w-3.5" aria-hidden="true" />
                                {{ __('Renvoyer l’e-mail de vérification') }}
                            </button>
                        </form>
                    @endunless
                </div>

                <div class="rounded-2xl bg-bmlk-page p-5 sm:p-7">
                    <h2 class="mb-5 font-serif text-xl text-zinc-900">{{ __('Définir mon mot de passe') }}</h2>
                    @include('account.partials.password')
                </div>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            <section class="rounded-3xl bg-white p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <x-lucide-package class="h-5 w-5 text-bmlk-brand" />
                    <h2 class="font-serif text-2xl text-zinc-900">{{ __('Mes commandes') }}</h2>
                </div>

                @forelse ($orders as $order)
                    <a href="{{ route('account.order', $order) }}" class="group mt-5 flex flex-wrap items-start justify-between gap-4 border-b border-zinc-100 pb-5 transition last:border-0">
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2.5">
                                <strong class="text-sm font-semibold text-zinc-900">{{ $order->order_number }}</strong>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium {{ in_array($order->status, ['shipped', 'completed']) ? 'bg-emerald-50 text-emerald-700' : ($order->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-zinc-100 text-zinc-600') }}">
                                    <x-lucide-package-check class="h-3.5 w-3.5" aria-hidden="true" />
                                    {{ $order->customerStatusLabel() }}
                                </span>
                            </span>
                            <span class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500">
                                <span>{{ $order->created_at->format('d.m.Y') }}</span>
                                <span aria-hidden="true">·</span>
                                <span>{{ $order->customerPaymentStatusLabel() }}</span>
                            </span>
                            @if (filled($order->tracking_number))
                                <span class="mt-2 flex min-w-0 items-start gap-1.5 text-xs text-zinc-600">
                                    <x-lucide-truck class="mt-0.5 h-3.5 w-3.5 shrink-0 text-bmlk-brand" aria-hidden="true" />
                                    <span class="min-w-0 break-all">{{ __('Numéro de suivi') }} : <span class="font-medium text-zinc-900">{{ $order->tracking_number }}</span></span>
                                </span>
                            @endif
                        </span>
                        <span class="flex shrink-0 items-center gap-3 text-sm font-semibold text-zinc-900">
                            {{ number_format($order->total_amount, 2, ',', ' ') }} €
                            <x-lucide-chevron-right class="h-4 w-4 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-bmlk-brand" aria-hidden="true" />
                        </span>
                    </a>
                @empty
                    <p class="mt-6 text-sm text-zinc-500">{{ __('Aucune commande pour le moment.') }}</p>
                @endforelse
                <div class="mt-6">{{ $orders->links() }}</div>
            </section>

            <aside>
                <section class="rounded-3xl bg-white p-6">
                    <x-lucide-user-round class="h-5 w-5 text-bmlk-brand" />
                    <h2 class="mt-4 font-serif text-xl text-zinc-900">{{ __('Bienvenue, :name', ['name' => $user->name]) }}</h2>
                    <p class="mt-2 break-all text-sm text-zinc-500">{{ $user->email }}</p>
                    <a href="{{ route('account.roles') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-bmlk-brand">
                        <x-lucide-handshake class="h-4 w-4" aria-hidden="true" />
                        {{ __('Découvrir les partenariats') }}
                        <x-lucide-arrow-up-right class="h-4 w-4" />
                    </a>
                </section>
            </aside>
        </div>
    </main>
</x-layout>
