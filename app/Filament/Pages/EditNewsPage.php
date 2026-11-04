<?php

namespace App\Filament\Pages;

use App\Filament\Resources\NewsArticles\NewsArticleResource;
use App\Models\NewsArticle;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;

/**
 * The newsroom page (/news) in the page editor. Opening it in the menu lists the stories beneath it: every story,
 * add a story, and the latest ones. A story is a record with its own form because new ones are added all the time.
 */
class EditNewsPage extends EditPageSections
{
    protected static string $pageKey = 'news';

    protected static ?string $slug = 'pages/news';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = -4;

    public static function getNavigationLabel(): string
    {
        return __('Newsroom');
    }

    public function getTitle(): string
    {
        return __('Newsroom');
    }

    /** @return list<NavigationItem> */
    public static function navigationItems(): array
    {
        if (! NewsArticleResource::canViewAny()) {
            return [];
        }
        $locale = app()->getLocale();
        $base = NewsArticleResource::getRouteBaseName();
        $items = [
            NavigationItem::make('news-all')->label(__('All stories'))->group(__('Pages'))->parentItem(__('Newsroom'))->sort(1)
                ->url(fn () => NewsArticleResource::getUrl())->isActiveWhen(fn () => request()->routeIs("{$base}.index")),
            NavigationItem::make('news-add')->label(__('Add a story'))->group(__('Pages'))->parentItem(__('Newsroom'))->sort(2)
                ->url(fn () => NewsArticleResource::getUrl('create'))->isActiveWhen(fn () => request()->routeIs("{$base}.create"))
                ->visible(fn () => NewsArticleResource::canCreate()),
        ];
        foreach (NewsArticle::query()->orderByDesc('published_on')->orderByDesc('id')->limit(6)->withLocale($locale)->get() as $i => $article) {
            $items[] = NavigationItem::make('news-'.$article->id)->label(str((string) $article->t('title', $locale))->limit(38)->toString())->group(__('Pages'))->parentItem(__('Newsroom'))->sort(3 + $i)
                ->url(fn () => NewsArticleResource::getUrl('edit', ['record' => $article]))
                ->isActiveWhen(fn () => request()->routeIs("{$base}.edit") && (string) (request()->route('record')?->id ?? request()->route('record')) === (string) $article->id);
        }

        return $items;
    }
}
