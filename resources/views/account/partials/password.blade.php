<form method="POST" action="{{ route('account.password') }}" x-data="{
    generated: false, visible: false,
    makePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        const values = new Uint32Array(22);
        crypto.getRandomValues(values);
        const password = Array.from(values, n => chars[n % chars.length]).join('');
        this.$refs.password.value = password;
        this.$refs.confirm.value = password;
        this.visible = true;
        this.generated = true;
    }
}" class="space-y-4">
    @csrf
    <p class="text-sm leading-6 text-zinc-500">
        {{ __('Créez votre mot de passe pour retrouver votre compte sur tous vos appareils. Votre navigateur peut le sauvegarder.') }}
    </p>
    @if (auth()->user()->password)
    <div>
        <label for="current-password" class="mb-2 block text-sm font-medium">{{ __('Mot de passe actuel') }}</label>
        <input id="current-password" name="current_password" type="password" autocomplete="current-password" required
            class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none focus:border-bmlk-brand">
    </div>
    @endif
    <div>
        <label for="account-password" class="mb-2 block text-sm font-medium">{{ __('Mot de passe') }}</label>
        <input x-ref="password" id="account-password" name="password" :type="visible ? 'text' : 'password'"
            autocomplete="new-password" minlength="12" required
            class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none focus:border-bmlk-brand">
    </div>
    <div>
        <label for="account-confirm" class="mb-2 block text-sm font-medium">{{ __('Confirmer le mot de passe') }}</label>
        <input x-ref="confirm" id="account-confirm" name="password_confirmation" :type="visible ? 'text' : 'password'"
            autocomplete="new-password" minlength="12" required
            class="w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none focus:border-bmlk-brand">
    </div>
    <div class="flex flex-wrap items-center gap-4">
        <button type="button" @click="makePassword()" class="inline-flex items-center gap-2 text-sm font-medium text-bmlk-brand">
            <x-lucide-shuffle class="h-4 w-4" />{{ __('Générer un mot de passe') }}
        </button>
        <button type="button" @click="visible = !visible" class="text-sm text-zinc-500">{{ __('Afficher / masquer') }}</button>
    </div>
    <button type="submit" class="rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white hover:opacity-90">
        {{ __('Enregistrer mon mot de passe') }}
    </button>
</form>
