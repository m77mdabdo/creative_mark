<?php

namespace App\Filament\Pages;

use App\Content\ServicePageBuilder;
use App\Models\Service;
use Filament\Navigation\NavigationItem;
use Livewire\Attributes\Locked;

/**
 * One business solution's own page in the page editor (Pages › Business solutions › each solution). The menu lists
 * every solution beneath Business solutions; the page is created from the service record if it has none yet.
 */
class EditServicePage extends EditPageSections
{
    protected static ?string $slug = 'pages/services/{service}';

    protected static bool $shouldRegisterNavigation = false;

    #[Locked]
    public string $service = '';

    public function mount(?string $service = null): void
    {
        $record = Service::query()->where('slug', $service ?? $this->service)->with('translations')->firstOrFail();
        $this->service = $record->slug;
        ServicePageBuilder::ensure($record);
        parent::mount();
    }

    protected function pageKey(): string
    {
        return ServicePageBuilder::key($this->service);
    }

    public function getTitle(): string
    {
        $service = Service::query()->where('slug', $this->service)->withLocale(app()->getLocale())->first();

        return $service?->t('title', app()->getLocale()) ?? $this->service;
    }

    public function getBreadcrumbs(): array
    {
        return [EditServicesPage::getUrl() => __('Business solutions'), $this->getTitle()];
    }

    /** One menu entry per business solution under Pages › Business solutions, in catalogue order. @return list<NavigationItem> */
    public static function navigationItems(): array
    {
        $locale = app()->getLocale();

        return Service::query()->orderBy('sort_order')->withLocale($locale)->get()->map(fn (Service $s) => NavigationItem::make('service-'.$s->slug)
            ->label($s->t('title', $locale) ?? $s->slug)
            ->group(__('Pages'))
            ->parentItem(__('Business solutions'))
            ->sort($s->sort_order)
            ->url(fn () => static::getUrl(['service' => $s->slug]))
            ->isActiveWhen(fn () => request()->routeIs(static::getRouteName()) && request()->route('service') === $s->slug)
            ->visible(fn () => static::canAccess()))->all();
    }
}
