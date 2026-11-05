<x-filament-panels::page>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-filament::input.wrapper class="w-full sm:max-w-sm" prefix-icon="heroicon-o-magnifying-glass">
            <x-filament::input type="search" wire:model.live.debounce.400ms="search" :placeholder="__('Search the images')" />
        </x-filament::input.wrapper>
        <div class="flex flex-wrap gap-2">
            @foreach (['all' => __('All images'), 'uploads' => __('My uploads'), 'unused' => __('Not used')] as $value => $label)
                <x-filament::button size="sm" :color="$show === $value ? 'primary' : 'gray'" wire:click="setShow('{{ $value }}')">{{ $label }}</x-filament::button>
            @endforeach
        </div>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __(':count images', ['count' => $total]) }}</p>

    @if (empty($items))
        <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-white/10">{{ __('No images match.') }}</div>
    @endif

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
        @foreach ($items as $item)
            <figure wire:key="media-{{ $item['key'] }}" class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
                <div class="relative aspect-[4/3] bg-gray-100 dark:bg-white/5">
                    <img src="{{ $item['url'] }}" alt="{{ $item['alt'] }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                    @if ($item['uploaded'])
                        <span class="absolute start-2 top-2 rounded-full bg-primary-500 px-2 py-0.5 text-xs font-semibold text-gray-950">{{ __('Uploaded') }}</span>
                    @endif
                </div>
                <figcaption class="flex flex-1 flex-col gap-2 p-3">
                    <p class="line-clamp-2 text-sm font-medium text-gray-900 dark:text-white" dir="auto">{{ $item['alt'] }}</p>
                    <details class="text-xs text-gray-500 dark:text-gray-400">
                        <summary class="cursor-pointer">{{ match (count($item['places'])) { 0 => __('Not used'), 1 => __('Used in 1 place'), default => __('Used in :count places', ['count' => count($item['places'])]) } }}</summary>
                        <ul class="mt-1 space-y-1">
                            @foreach ($item['places'] as $place)
                                <li dir="auto">{{ $place }}</li>
                            @endforeach
                        </ul>
                    </details>
                    <div class="mt-auto flex items-center justify-between gap-2 pt-1">
                        <x-filament::link size="sm" tag="button" wire:click="mountAction('edit', { key: '{{ $item['key'] }}' })">{{ __('Edit') }}</x-filament::link>
                        @if ($item['canDelete'])
                            <x-filament::link size="sm" color="danger" tag="button" wire:click="mountAction('delete', { key: '{{ $item['key'] }}' })">{{ __('Delete') }}</x-filament::link>
                        @endif
                    </div>
                </figcaption>
            </figure>
        @endforeach
    </div>

    @if ($total > count($items))
        <div class="text-center">
            <x-filament::button color="gray" wire:click="loadMore">{{ __('Show more') }}</x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
