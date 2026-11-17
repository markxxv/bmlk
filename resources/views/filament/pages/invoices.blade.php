@php
    $exports = $this->getExports();
    $hasActive = $exports->contains(fn ($e) => $e->isActive());
@endphp

<x-filament-panels::page>
    <div @if ($hasActive) wire:poll.4s @endif class="flex flex-col gap-6">
        {{-- New archive --}}
        <x-filament::section icon="lucide-file-plus-2" icon-color="primary">
            <x-slot name="heading">New archive</x-slot>
            <x-slot name="description">Pick a period. All paid orders become invoices, refunds become credit notes, packed in one ZIP.</x-slot>

            <form wire:submit="createExport" class="flex flex-col gap-4">
                {{ $this->form }}

                <div class="flex flex-wrap gap-2">
                    <x-filament::button type="button" color="gray" size="sm" icon="lucide-calendar-range" wire:click="setRange('previous-month')">Last month</x-filament::button>
                    <x-filament::button type="button" color="gray" size="sm" icon="lucide-calendar-days" wire:click="setRange('this-month')">This month</x-filament::button>
                    <x-filament::button type="button" color="gray" size="sm" icon="lucide-calendar-clock" wire:click="setRange('last-7')">Last 7 days</x-filament::button>
                </div>

                <div>
                    <x-filament::button type="submit" icon="lucide-file-archive" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="createExport">
                        Create archive
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Archives --}}
        <x-filament::section icon="lucide-archive" icon-color="gray">
            <x-slot name="heading">Archives</x-slot>
            <x-slot name="description">Files are kept for 14 days, then removed automatically.</x-slot>

            @if ($exports->isEmpty())
                <div class="flex flex-col items-center gap-2 py-8 text-center text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="lucide-inbox" class="h-10 w-10" />
                    <p class="text-sm">No archives yet. Create the first one above.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($exports as $export)
                        <li wire:key="export-{{ $export->id }}" class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-white/5">
                                    <x-filament::icon icon="lucide-file-archive" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                            {{ $export->date_from->format('d.m.Y') }} – {{ $export->date_to->format('d.m.Y') }}
                                        </span>
                                        @switch($export->status)
                                            @case('ready')
                                                <x-filament::badge color="success" icon="lucide-circle-check">Ready</x-filament::badge>
                                                @break
                                            @case('processing')
                                                <x-filament::badge color="warning" icon="lucide-loader">Building</x-filament::badge>
                                                @break
                                            @case('pending')
                                                <x-filament::badge color="gray" icon="lucide-clock">Queued</x-filament::badge>
                                                @break
                                            @default
                                                <x-filament::badge color="danger" icon="lucide-circle-alert">Failed</x-filament::badge>
                                        @endswitch
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @if ($export->status === 'ready')
                                            {{ $export->invoices_count }} PDF · {{ number_format($export->file_size / 1048576, 1) }} MB ·
                                        @endif
                                        requested {{ $export->created_at->diffForHumans() }}
                                        @if ($export->requester) · {{ $export->requester->name ?: $export->requester->email }} @endif
                                    </p>
                                    @if ($export->status === 'failed' && $export->error)
                                        <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $export->error }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 sm:shrink-0">
                                @if ($export->isActive())
                                    <div class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300 sm:flex-none">
                                        <x-filament::loading-indicator class="h-5 w-5" />
                                        <span>{{ $export->status === 'processing' ? 'Building…' : 'Waiting to start…' }}</span>
                                    </div>
                                @elseif ($export->isReady())
                                    <x-filament::button tag="a" :href="$export->downloadUrl()" icon="lucide-download" color="success" class="flex-1 sm:flex-none">
                                        Download
                                    </x-filament::button>
                                @endif

                                @unless ($export->isActive())
                                    {{ ($this->deleteAction)(['export' => $export->id]) }}
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
