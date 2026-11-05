<?php

namespace App\Filament\Pages;

/** The fixed wording on every story's page ("Related news", "Read the original report", the closing invitation…). */
class EditNewsLabels extends EditFixedTexts
{
    public const LABELS = [
        'whatItMeans' => ['Heading: what it means', false],
        'summaryBy' => ['Note under the story', true],
        'source' => ['Heading: source', false],
        'reportDate' => ['Label: report date', false],
        'readOriginal' => ['Button: read the original', false],
        'copyLink' => ['Button: copy link', false],
        'linkCopied' => ['Message: link copied', false],
        'latest' => ['Heading: latest', false],
        'relatedNews' => ['Heading: related news', false],
        'allNews' => ['Link: all news', false],
        'minRead' => ['Label: minutes to read', false],
        'ctaTitle' => ['Closing section: title', false],
        'ctaBody' => ['Closing section: text', true],
        'bookConsultation' => ['Closing section: button', false],
    ];

    protected const GROUP = 'news';

    protected static ?string $slug = 'pages/news/story-texts';

    public function getTitle(): string
    {
        return __('Story page texts');
    }

    public function getSubheading(): string
    {
        return __('The fixed wording on every story’s page. Changes appear on the website when you save.');
    }

    public function getBreadcrumbs(): array
    {
        return [EditNewsPage::getUrl() => __('Newsroom'), $this->getTitle()];
    }
}
