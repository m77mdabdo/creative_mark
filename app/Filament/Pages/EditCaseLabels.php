<?php

namespace App\Filament\Pages;

/** The fixed wording on every case study's page (chapter names, the at-a-glance bar, the closing invitation…). */
class EditCaseLabels extends EditFixedTexts
{
    public const LABELS = [
        'startingPoint' => ['Chapter: the starting point', false],
        'ourSteps' => ['Chapter: our steps', false],
        'whatDelivered' => ['Chapter: what was delivered', false],
        'whereItLeads' => ['Chapter: where it leads', false],
        'step' => ['Label: step', false],
        'atAGlance' => ['Heading: at a glance', false],
        'sector' => ['Label: sector', false],
        'clientFrom' => ['Label: client from', false],
        'regionLabel' => ['Label: region', false],
        'legalForm' => ['Label: legal form', false],
        'servicesUsed' => ['Heading: services used', false],
        'moreCases' => ['Heading: more cases', false],
        'illustrative' => ['Badge: illustrative example', false],
        'illustrativeNote' => ['Note on illustrative examples', true],
        'ctaTitle' => ['Closing section: title', false],
        'ctaBody' => ['Closing section: text', true],
        'bookConsultation' => ['Closing section: button', false],
    ];

    protected const GROUP = 'cases';

    protected static ?string $slug = 'pages/case-studies/case-texts';

    public function getTitle(): string
    {
        return __('Case page texts');
    }

    public function getSubheading(): string
    {
        return __('The fixed wording on every case study’s page. Changes appear on the website when you save.');
    }

    public function getBreadcrumbs(): array
    {
        return [EditCaseStudiesPage::getUrl() => __('Case studies'), $this->getTitle()];
    }
}
