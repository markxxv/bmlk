<x-filament-panels::page>
    @viteReactRefresh
    <form wire:submit="save" novalidate class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Edit the hero in every language, then publish your changes.</p>
            <x-filament::button type="submit" icon="lucide-check" wire:loading.attr="disabled" wire:target="save">Publish changes</x-filament::button>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-xl bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-950 dark:text-danger-300">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $this->form }}
    </form>
</x-filament-panels::page>
