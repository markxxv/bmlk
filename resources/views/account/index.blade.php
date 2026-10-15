<x-layout :title="__('Mes commandes | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">
    @include('account.partials.nav')

<div class="grid gap-6 lg:grid-cols-[1fr_340px]">
    <section class="rounded-3xl bg-white p-6 sm:p-8">
        <div class="flex items-center gap-3"><x-lucide-package class="h-5 w-5 text-bmlk-brand" />
            <h1 class="font-serif text-2xl text-zinc-900">{{ __('Mes commandes') }}</h1>
        </div>
        @forelse ($orders as $order)
            <a href="{{ route('account.order', $order) }}" class="mt-5 flex items-center justify-between gap-4 border-b border-zinc-100 pb-5 last:border-0">
                <span>
                    <strong class="block text-sm font-medium text-zinc-900">{{ $order->order_number }}</strong>
                    <span class="mt-1 block text-xs text-zinc-500">{{ $order->created_at->format('d.m.Y') }} · {{ $order->payment_status === 'paid' ? __('Payée') : __('Paiement en attente') }}</span>
                </span>
                <span class="flex items-center gap-3 text-sm font-medium text-zinc-900">{{ number_format($order->total_amount, 2, ',', ' ') }} €
                    <x-lucide-chevron-right class="h-4 w-4 text-zinc-400" /></span>
            </a>
        @empty
            <p class="mt-6 text-sm text-zinc-500">{{ __('Aucune commande pour le moment.') }}</p>
        @endforelse
        <div class="mt-6">{{ $orders->links() }}</div>
    </section>
    <aside class="space-y-6">
        <section class="rounded-3xl bg-white p-6">
            <x-lucide-user-round class="h-5 w-5 text-bmlk-brand" />
            <h2 class="mt-4 font-serif text-xl text-zinc-900">{{ __('Bienvenue, :name', ['name' => $user->name]) }}</h2>
            <p class="mt-2 break-all text-sm text-zinc-500">{{ $user->email }}</p>
            <a href="{{ route('account.roles') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-bmlk-brand">
                {{ __('Découvrir les partenariats') }} <x-lucide-arrow-up-right class="h-4 w-4" />
            </a>
        </section>
        @if (! $user->password)
            <section class="rounded-3xl bg-white p-6">
                <h2 class="font-serif text-xl text-zinc-900">{{ __('Protégez votre compte') }}</h2>
                <div class="mt-5">@include('account.partials.password')</div>
            </section>
        @endif
    </aside>
</div>

</main>
</x-layout>
