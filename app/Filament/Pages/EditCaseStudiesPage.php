<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CaseStudies\CaseStudyResource;
use App\Models\CaseStudy;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;

/**
 * The case studies overview (/case-studies) in the page editor. Opening it in the menu lists the cases beneath it:
 * every case, add a case, each case and the wording shared by all case pages.
 */
class EditCaseStudiesPage extends EditPageSections
{
    protected static string $pageKey = 'case-studies';

    protected static ?string $slug = 'pages/case-studies';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = -3;

    public static function getNavigationLabel(): string
    {
        return __('Case studies');
    }

    public function getTitle(): string
    {
        return __('Case studies');
    }

    /** @return list<NavigationItem> */
    public static function navigationItems(): array
    {
        if (! CaseStudyResource::canViewAny()) {
            return [];
        }
        $locale = app()->getLocale();
        $base = CaseStudyResource::getRouteBaseName();
        $parent = __('Case studies');
        $items = [
            NavigationItem::make('cases-all')->label(__('All cases'))->group(__('Pages'))->parentItem($parent)->sort(1)
                ->url(fn () => CaseStudyResource::getUrl())->isActiveWhen(fn () => request()->routeIs("{$base}.index")),
            NavigationItem::make('cases-add')->label(__('Add a case'))->group(__('Pages'))->parentItem($parent)->sort(2)
                ->url(fn () => CaseStudyResource::getUrl('create'))->isActiveWhen(fn () => request()->routeIs("{$base}.create"))
                ->visible(fn () => CaseStudyResource::canCreate()),
            NavigationItem::make('cases-texts')->label(__('Case page texts'))->group(__('Pages'))->parentItem($parent)->sort(50)
                ->url(fn () => EditCaseLabels::getUrl())->isActiveWhen(fn () => request()->routeIs(EditCaseLabels::getRouteName())),
        ];
        foreach (CaseStudy::query()->orderBy('sort_order')->limit(8)->withLocale($locale)->get() as $i => $case) {
            $items[] = NavigationItem::make('case-'.$case->id)->label(str((string) $case->t('title', $locale))->limit(38)->toString())->group(__('Pages'))->parentItem($parent)->sort(3 + $i)
                ->url(fn () => CaseStudyResource::getUrl('edit', ['record' => $case]))
                ->isActiveWhen(fn () => request()->routeIs("{$base}.edit") && (string) (request()->route('record')?->id ?? request()->route('record')) === (string) $case->id);
        }

        return $items;
    }
}
