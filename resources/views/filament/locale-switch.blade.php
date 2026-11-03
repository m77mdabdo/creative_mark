@php($current = app()->getLocale())
<form method="POST" action="{{ route('admin.locale') }}" class="flex items-center">
    @csrf
    <input type="hidden" name="locale" value="{{ $current === 'ar' ? 'en' : 'ar' }}">
    <button type="submit" title="{{ $current === 'ar' ? 'English' : 'العربية' }}"
        class="inline-flex h-9 items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-primary-400 hover:text-primary-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
        <x-filament::icon icon="heroicon-o-language" class="h-4 w-4" />
        {{ $current === 'ar' ? 'EN' : 'عربي' }}
    </button>
</form>
