<?php

namespace App\Filament\Widgets;

use App\Enums\ReviewStatus;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Service;
use App\Models\Stat as StatModel;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Published content and what still needs review, for content staff. */
class ContentOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('content.edit') || (bool) auth()->user()?->can('translations.edit');
    }

    protected function getHeading(): ?string
    {
        return __('Content health');
    }

    /** Two figures per row on phones, four on large screens. */
    protected function getColumns(): int|array
    {
        return ['default' => 2, 'lg' => 4];
    }

    protected function getStats(): array
    {
        $pagesToReview = Page::query()->whereHas('translations', fn ($q) => $q->whereIn('review_status', [ReviewStatus::Machine, ReviewStatus::Review]))->count();
        $faqs = Faq::query()->where('needs_review', true)->count();
        $unverified = StatModel::query()->where('verified', false)->count();

        return [
            Stat::make(__('Published content'), Page::query()->whereNotNull('published_revision_id')->count())
                ->description(Service::query()->live()->count().' '.__('Services'))->descriptionIcon('heroicon-m-document-text')->color('success'),
            Stat::make(__('Published stories'), NewsArticle::query()->live()->count())
                ->description(CaseStudy::query()->live()->count().' '.__('Case studies live'))->descriptionIcon('heroicon-m-newspaper')->color('info'),
            Stat::make(__('Pages with languages to review'), $pagesToReview)
                ->description($pagesToReview ? __('translations') : __('Everything is reviewed'))->descriptionIcon('heroicon-m-language')->color($pagesToReview ? 'warning' : 'success'),
            Stat::make(__('Unverified figures'), $unverified)
                ->description($faqs.' '.__('FAQs waiting for review'))->descriptionIcon('heroicon-m-shield-exclamation')->color($unverified || $faqs ? 'danger' : 'success'),
        ];
    }
}
