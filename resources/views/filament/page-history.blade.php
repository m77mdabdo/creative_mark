{{-- Version history of a page: newest first, the live version marked, any version can be restored into the draft. --}}
<div class="divide-y divide-gray-100 dark:divide-white/10">
    @forelse ($revisions as $revision)
        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                    <span>#{{ $revision->id }}</span>
                    @if ($revision->id === $liveId)
                        <x-filament::badge color="success">{{ __('On the website') }}</x-filament::badge>
                    @elseif ($revision->kind === 'publish')
                        <x-filament::badge color="info">{{ __('Published') }}</x-filament::badge>
                    @else
                        <x-filament::badge color="gray">{{ __('Draft') }}</x-filament::badge>
                    @endif
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $revision->created_at?->locale(app()->getLocale())->translatedFormat('j M Y · H:i') }}
                    · {{ $revision->author?->name ?? __('System') }}
                    · {{ count($revision->sections ?? []) }} {{ __('sections') }}
                </p>
            </div>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-uturn-left"
                wire:click="restoreRevision({{ $revision->id }})"
                wire:confirm="{{ __('Put this version back into the draft? Your current draft is kept in the history.') }}">
                {{ __('Restore') }}
            </x-filament::button>
        </div>
    @empty
        <p class="py-6 text-center text-sm text-gray-500">{{ __('No versions yet.') }}</p>
    @endforelse
</div>
