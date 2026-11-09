<x-warehouse.layout title="Sign in">
    <div class="mx-auto flex min-h-[75dvh] max-w-sm flex-col justify-center">
        <div class="mb-10 flex justify-center">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="{{ __('BLACK MILK') }}">
                <img src="/img/logo.png" alt="" class="h-14 w-14 shrink-0 object-contain">
                <span>
                    <span class="block whitespace-nowrap font-serif text-2xl font-medium leading-none tracking-widest text-zinc-900">{{ __('BLACK MILK') }}</span>
                    <span class="mt-1 block font-serif text-sm italic leading-none text-bmlk-brand">{{ __("The professionals' choice") }}</span>
                </span>
            </a>
        </div>
        <form method="POST" action="{{ route('warehouse.login.submit') }}" class="space-y-4 rounded-3xl bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold" for="email">{{ __('Email') }}</label>
                <input class="w-full rounded-xl bg-zinc-100 px-4 py-3.5 text-base outline-none focus:ring-2 focus:ring-[#712B47]" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold" for="password">{{ __('Password') }}</label>
                <input class="w-full rounded-xl bg-zinc-100 px-4 py-3.5 text-base outline-none focus:ring-2 focus:ring-[#712B47]" id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            @if ($errors->any())
                <p role="alert" class="text-sm text-rose-700">{{ $errors->first() }}</p>
            @endif
            <button class="flex min-h-13 w-full items-center justify-center rounded-xl bg-[#712B47] px-5 py-3 font-semibold text-white active:opacity-80" type="submit">{{ __('Sign in') }}</button>
        </form>
    </div>
</x-warehouse.layout>
