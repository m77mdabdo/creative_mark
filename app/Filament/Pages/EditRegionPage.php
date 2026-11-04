<?php

namespace App\Filament\Pages;

use App\Content\RegionPageBuilder;
use App\Models\Page as PageModel;
use App\Models\Region;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Locked;

/**
 * One region's own page in the page editor (Pages › Saudi regions › each region). The page is created from the
 * region record if it has none yet; publishing copies its name, focus, text and opportunities back to the record.
 */
class EditRegionPage extends EditPageSections
{
    protected static ?string $slug = 'pages/regions/{region}';

    protected static bool $shouldRegisterNavigation = false;

    #[Locked]
    public string $region = '';

    public function mount(?string $region = null): void
    {
        $record = Region::query()->where('slug', $region ?? $this->region)->with('translations')->firstOrFail();
        $this->region = $record->slug;
        RegionPageBuilder::ensure($record);
        parent::mount();
    }

    protected function pageKey(): string
    {
        return RegionPageBuilder::key($this->region);
    }

    protected function afterPublish(PageModel $page): void
    {
        RegionPageBuilder::syncToRegion($page);
    }

    public function getTitle(): string
    {
        return Region::query()->where('slug', $this->region)->withLocale(app()->getLocale())->first()?->t('name', app()->getLocale()) ?? $this->region;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return parent::getSubheading().' · '.__('Publishing also updates this region’s card and its place on the map.');
    }

    public function getBreadcrumbs(): array
    {
        return [EditRegionsPage::getUrl() => __('Saudi regions'), $this->getTitle()];
    }

    /** One menu entry per region under Pages › Saudi regions, in catalogue order. @return list<NavigationItem> */
    public static function navigationItems(): array
    {
        $locale = app()->getLocale();

        return Region::query()->orderBy('sort_order')->withLocale($locale)->get()->map(fn (Region $r) => NavigationItem::make('region-'.$r->slug)
            ->label($r->t('name', $locale) ?? $r->slug)
            ->group(__('Pages'))
            ->parentItem(__('Saudi regions'))
            ->sort($r->sort_order)
            ->url(fn () => static::getUrl(['region' => $r->slug]))
            ->isActiveWhen(fn () => request()->routeIs(static::getRouteName()) && request()->route('region') === $r->slug)
            ->visible(fn () => static::canAccess()))->all();
    }
}
