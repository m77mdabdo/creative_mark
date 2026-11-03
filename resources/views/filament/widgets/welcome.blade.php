@php
    $tones = [
        'amber' => 'background:#fef3c7;color:#b45309', 'sky' => 'background:#e0f2fe;color:#0369a1',
        'violet' => 'background:#ede9fe;color:#6d28d9', 'emerald' => 'background:#d1fae5;color:#047857',
        'rose' => 'background:#ffe4e6;color:#be123c', 'orange' => 'background:#ffedd5;color:#c2410c',
    ];
@endphp
<x-filament-widgets::widget>
    <div class="cm-hero">
        <div class="relative z-10 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm text-white/70">{{ $date }}</p>
                <h2 class="mt-1">{{ $greeting }}</h2>
                <p class="mt-2 max-w-xl text-sm text-white/75">{{ __('Here is what is happening at Creative Mark today.') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if (! is_null($newCount))
                        <span class="cm-chip"><span class="h-2 w-2 rounded-full bg-amber-400"></span><strong>{{ $newCount }}</strong> {{ __('new bookings') }}</span>
                        <span class="cm-chip"><span class="h-2 w-2 rounded-full bg-sky-400"></span><strong>{{ $weekCount }}</strong> {{ __('meetings this week') }}</span>
                    @endif
                </div>
            </div>
            <a href="{{ $siteUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 self-start rounded-full bg-[#ebbd6c] px-5 py-2.5 text-sm font-semibold text-[#141414] shadow-lg transition hover:bg-white md:self-auto">
                <x-filament::icon icon="heroicon-o-globe-alt" class="h-4 w-4" />{{ __('View website') }}
            </a>
        </div>
    </div>

    @if (count($actions))
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}" class="cm-action">
                    <span class="cm-action-icon" style="{{ $tones[$action['tone']] }}"><x-filament::icon :icon="$action['icon']" class="h-5 w-5" /></span>
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</x-filament-widgets::widget>
