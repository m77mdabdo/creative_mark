<?php

namespace App\Filament\Support;

use App\Content\MediaLibrary;
use App\Content\SectionSchema;
use App\Support\Locales;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Form for a page's sections (the typed blocks of App\Content\SectionSchema): one collapsible, reorderable item per
 * section with its texts in every language, images, buttons, cards, hero slides, design and motion.
 */
final class SectionForm
{
    /** Section types with editable cards / slides / a number limit. */
    private const WITH_CARDS = ['whyInvest', 'sectors', 'cards', 'checklist', 'guideChapter', 'serviceOverview', 'comparison', 'regionOverview'];

    private const WITH_LIMIT = ['services', 'faq', 'newsGrid', 'caseGrid', 'relatedServices', 'moreRegions'];

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            'hero' => __('Hero (opening slideshow)'), 'whyInvest' => __('Why invest'), 'journey' => __('Investor journey'),
            'countries' => __('Countries we serve'), 'sectors' => __('Sectors'), 'trackRecord' => __('Track record (figures)'),
            'services' => __('Business solutions'), 'map' => __('Saudi map'), 'faq' => __('Questions'), 'cta' => __('Call to action'),
            'testimonials' => __('Testimonials'), 'statement' => __('Statement'), 'imageText' => __('Image and text'),
            'cards' => __('Cards'), 'serviceGroups' => __('Solution groups'), 'processSteps' => __('Process steps'),
            'comparison' => __('Comparison'), 'regionGrid' => __('Regions grid'), 'guideNav' => __('Guide contents'),
            'guideChapter' => __('Guide chapter'), 'checklist' => __('Checklist'), 'sourceList' => __('Sources'),
            'newsLead' => __('Lead story'), 'newsGrid' => __('News grid'), 'caseGrid' => __('Case studies grid'),
            'faqLibrary' => __('All questions'), 'contactInfo' => __('Offices and contacts'), 'legalText' => __('Legal text'),
            'serviceOverview' => __('Overview and what is included'), 'relatedServices' => __('Related solutions'),
            'regionOverview' => __('Opportunity, map and sectors'), 'moreRegions' => __('More regions'),
            default => (string) $type,
        };
    }

    /**
     * The list of sections: each row can be moved, shown or hidden, duplicated and deleted. Its fields open in a side
     * panel on demand ("Edit"), so even long pages stay light on phones. Row state holds the whole section.
     */
    public static function make(): Repeater
    {
        return Repeater::make('sections')
            ->hiddenLabel()
            ->schema([
                Hidden::make('id'),
                Hidden::make('type')->visible(fn (Get $get) => filled($get('id'))),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Select::make('type')->label(__('Section type'))->required()->native(false)->live()
                        ->options(collect(SectionSchema::TYPES)->mapWithKeys(fn ($t) => [$t => self::typeLabel($t)]))
                        ->visible(fn (Get $get) => blank($get('id')))
                        ->helperText(__('Choose the type, then press Edit to fill it in.')),
                    Toggle::make('visible')->label(__('Show on the website'))->default(true),
                ]),
            ])
            ->reorderable()->reorderableWithButtons()->reorderableWithDragAndDrop()
            ->cloneable()
            ->itemLabel(function (array $state): string {
                $title = $state['title'][app()->getLocale()] ?? $state['title']['en'] ?? null;

                return (($state['visible'] ?? true) ? '' : '🚫 '.__('Hidden').' · ').self::typeLabel($state['type'] ?? null)
                    .(filled($title) ? ' · '.Str::limit($title, 50) : (filled($state['label'] ?? null) ? ' · '.$state['label'] : ''));
            })
            ->extraItemActions([
                Action::make('editSection')->label(__('Edit'))->icon('heroicon-o-pencil-square')->color('primary')
                    ->slideOver()->modalWidth('5xl')
                    ->modalHeading(fn (array $arguments, Repeater $component) => self::typeLabel($component->getRawState()[$arguments['item']]['type'] ?? null))
                    ->modalSubmitActionLabel(__('Done'))
                    ->fillForm(fn (array $arguments, Repeater $component) => $component->getRawState()[$arguments['item']] ?? [])
                    ->schema(self::sectionSchema())
                    ->action(function (array $data, array $arguments, Repeater $component) {
                        $state = $component->getRawState();
                        $state[$arguments['item']] = [...($state[$arguments['item']] ?? []), ...$data];
                        $component->rawState($state);
                    }),
            ])
            ->addActionLabel(__('Add a section'))
            ->deleteAction(fn ($action) => $action->requiresConfirmation())
            ->columnSpanFull();
    }

    /** @return array<int, mixed> */
    private static function sectionSchema(): array
    {
        $is = fn (array $types) => fn (Get $get) => in_array($get('type'), $types, true);

        return [
            Hidden::make('id'),
            Hidden::make('type'),
            TextInput::make('label')->label(__('Internal name'))->maxLength(80)->helperText(__('Only shown in the dashboard.')),

            self::localeTabs(fn (string $l, bool $en) => [
                TextInput::make("eyebrow.{$l}")->label(__('Small title above')),
                TextInput::make("title.{$l}")->label(__('Title')),
                TextInput::make("highlight.{$l}")->label(__('Gold words'))->helperText(__('Part of the title shown in gold; copy it exactly.')),
                Textarea::make("subtitle.{$l}")->label(__('Subtitle'))->rows(2),
                Textarea::make("body.{$l}")->label(__('Text'))->rows(4)->helperText(__('Leave a blank line between paragraphs.')),
                Textarea::make("points.{$l}")->label(__('Points'))->rows(4)->helperText(__('One point per line.'))->visible($is(['guideChapter'])),
                Textarea::make("note.{$l}")->label(__('Highlighted note'))->rows(2)->visible($is(['serviceOverview', 'guideChapter', 'legalText', 'sourceList'])),
            ], __('Texts')),

            Grid::make(['default' => 1, 'md' => 2])->schema([
                self::mediaSelect('media', __('Main image')),
                self::mediaSelect('media2', __('Second image')),
            ]),

            Repeater::make('slides')->label(__('Slides'))->visible($is(['hero']))
                ->schema([
                    Hidden::make('id'),
                    self::mediaSelect('media', __('Image'))->required(),
                    TextInput::make('region')->label(__('Region on the map'))->maxLength(40)->helperText(__('Optional, e.g. riyadh')),
                    self::localeTabs(fn (string $l) => [
                        TextInput::make("eyebrow.{$l}")->label(__('Small title above')),
                        TextInput::make("title.{$l}")->label(__('Title')),
                        TextInput::make("highlight.{$l}")->label(__('Gold words')),
                        Textarea::make("subtitle.{$l}")->label(__('Subtitle'))->rows(2),
                    ]),
                ])
                ->collapsible()->collapsed()->reorderableWithButtons()->cloneable()->maxItems(10)
                ->itemLabel(fn (array $state) => $state['title'][app()->getLocale()] ?? $state['title']['en'] ?? __('Slide'))
                ->addActionLabel(__('Add a slide')),

            Placeholder::make('comparison_hint')->hiddenLabel()->visible($is(['comparison']))
                ->content(new HtmlString('<div style="padding:.75rem 1rem;border-radius:.75rem;background:rgba(235,189,108,.15);font-size:.875rem">'.e(__('Keep two cards: the first is the plain column, the second the gold column. In each card’s text, write one item per line.')).'</div>')),
            Repeater::make('cards')->label(__('Cards'))->visible($is(self::WITH_CARDS))
                ->schema([
                    Hidden::make('id'),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        IconPicker::make('icon'),
                        self::mediaSelect('media', __('Image')),
                        Toggle::make('verified')->label(__('Figure verified'))->default(true)->inline(false)
                            ->helperText(__('Cards with an unverified figure are hidden (RULE-01).')),
                    ]),
                    self::localeTabs(fn (string $l) => [
                        TextInput::make("title.{$l}")->label(__('Title')),
                        Textarea::make("text.{$l}")->label(__('Text'))->rows(2),
                    ]),
                ])
                ->collapsible()->collapsed()->reorderableWithButtons()->cloneable()
                ->itemLabel(fn (array $state) => $state['title'][app()->getLocale()] ?? $state['title']['en'] ?? __('Card'))
                ->addActionLabel(__('Add a card')),

            Repeater::make('sources')->label(__('Sources'))->visible($is(['guideChapter', 'sourceList']))
                ->schema([
                    Hidden::make('id'),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        TextInput::make('href')->label(__('Link'))->required()->url()->maxLength(500)->extraInputAttributes(['dir' => 'ltr'])
                            ->rule('starts_with:https://')->columnSpan(['md' => 2])->helperText(__('Must start with https://')),
                        TextInput::make('date')->label(__('Date'))->maxLength(10)->rule('regex:/^\d{4}(-\d{2}){0,2}$/')->extraInputAttributes(['dir' => 'ltr'])
                            ->helperText(__('Optional: 2026, 2026-07 or 2026-07-15')),
                    ]),
                    self::localeTabs(fn (string $l, bool $en) => [
                        TextInput::make("label.{$l}")->label(__('Publication'))->required($en)->maxLength(200),
                        TextInput::make("publisher.{$l}")->label(__('Publisher'))->maxLength(120),
                    ]),
                ])
                ->collapsible()->collapsed()->reorderableWithButtons()->maxItems(30)
                ->itemLabel(fn (array $state) => $state['label'][app()->getLocale()] ?? $state['label']['en'] ?? __('Source'))
                ->addActionLabel(__('Add a source')),

            Placeholder::make('sources_hint')->hiddenLabel()->visible($is(['sourceList']))
                ->content(new HtmlString('<div style="padding:.75rem 1rem;border-radius:.75rem;background:rgba(235,189,108,.15);font-size:.875rem">'.e(__('This is the full library at the end of the guide. If you change a source in a chapter, change it here too.')).'</div>')),

            Repeater::make('buttons')->label(__('Buttons'))
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('href')->label(__('Link'))->required()->maxLength(300)->extraInputAttributes(['dir' => 'ltr'])
                            ->helperText(__('A page of the site (/consultation), a section (#map) or a full https link.'))
                            ->rule(fn () => function (string $attribute, $value, $fail) {
                                if (! SectionSchema::safeHref((string) $value)) {
                                    $fail(__('Use /page, #section, https://, mailto: or tel: links only.'));
                                }
                            }),
                        Select::make('style')->label(__('Style'))->required()->default('gold')->native(false)
                            ->options(['gold' => __('Gold'), 'outline' => __('Outline'), 'link' => __('Text link')]),
                    ]),
                    self::localeTabs(fn (string $l) => [TextInput::make("label.{$l}")->label(__('Button text'))->maxLength(60)]),
                ])
                ->collapsible()->reorderableWithButtons()->maxItems(3)
                ->itemLabel(fn (array $state) => $state['label'][app()->getLocale()] ?? $state['label']['en'] ?? __('Button'))
                ->addActionLabel(__('Add a button')),

            Placeholder::make('catalogue_hint')->hiddenLabel()
                ->visible($is(['trackRecord', 'testimonials', 'services', 'countries', 'map', 'faq', 'relatedServices', 'processSteps', 'regionOverview', 'regionGrid', 'guideNav', 'guideChapter', 'checklist', 'newsLead', 'newsGrid', 'caseGrid']))
                ->content(fn (Get $get) => new HtmlString('<div style="padding:.75rem 1rem;border-radius:.75rem;background:rgba(235,189,108,.15);font-size:.875rem">'.e(match ($get('type')) {
                    'trackRecord' => __('The figures come from Content › Statistics, or the “Figures” button at the top of this page.'),
                    'testimonials' => __('The reviews come from Content › Testimonials, or the “Client reviews” button at the top of this page.'),
                    'services' => __('The solutions come from Content › Services.'),
                    'countries' => __('The countries come from Content › Countries.'),
                    'map' => __('The regions come from Content › Regions.'),
                    'relatedServices' => __('These solutions are chosen on the service or region in the Content menu.'),
                    'regionOverview' => __('The map follows the region. Its sectors, capital and recommended solutions are set in Content › Regions.'),
                    'regionGrid' => __('The regions come from Content › Regions.'),
                    'newsLead' => __('The lead is the newest story marked as lead, from Pages › Newsroom in the menu.'),
                    'newsGrid' => __('The stories come from Pages › Newsroom in the menu. A number below shows only the latest ones, without filters.'),
                    'caseGrid' => __('The cases come from Content › Case Studies. A number below shows only the first ones, without filters.'),
                    'guideNav' => __('The contents list is built from the chapters below, in their order.'),
                    'guideChapter' => __('Chapters are numbered by their order on the page. In the cards, the title is the figure (such as 10 days) and the text explains it.'),
                    'checklist' => __('Each card is one step visitors can tick; ticks stay in their own browser.'),
                    'processSteps' => __('The steps come from Pages › Investor journey steps.'),
                    default => __('The questions come from Content › FAQs.'),
                }).'</div>')),
            TextInput::make('limit')->label(__('How many items to show'))->numeric()->minValue(1)->maxValue(50)->visible($is(self::WITH_LIMIT)),

            Section::make(__('Design and motion'))->collapsed()->compact()->columns(['default' => 1, 'md' => 3])->schema([
                Select::make('style.theme')->label(__('Background'))->required()->default('dark')->native(false)
                    ->options(['dark' => __('Black'), 'charcoal' => __('Charcoal'), 'ivory' => __('Ivory'), 'white' => __('White')]),
                Select::make('style.padding')->label(__('Spacing'))->default('lg')->native(false)
                    ->options(['sm' => __('Small'), 'md' => __('Medium'), 'lg' => __('Large')]),
                Select::make('style.align')->label(__('Alignment'))->default('start')->native(false)
                    ->options(['start' => __('Start'), 'center' => __('Centre')]),
                self::mediaSelect('style.bgMedia', __('Background image')),
                TextInput::make('style.overlay')->label(__('Image darkness (0–90)'))->numeric()->minValue(0)->maxValue(90)->default(55),
                Select::make('layout')->label(__('Image side'))->native(false)->placeholder(__('Default'))
                    ->options(['split' => __('Image first'), 'split-reverse' => __('Image second')]),
                Select::make('motion.preset')->label(__('Animation'))->required()->default('fade-up')->native(false)
                    ->options(collect(SectionSchema::MOTION)->mapWithKeys(fn ($m) => [$m => self::motionLabel($m)])),
                TextInput::make('motion.intensity')->label(__('Animation strength (0–100)'))->numeric()->minValue(0)->maxValue(100)->default(100),
                TextInput::make('motion.delay')->label(__('Delay (ms, 0–600)'))->numeric()->minValue(0)->maxValue(600)->default(0),
            ]),
        ];
    }

    public static function motionLabel(string $preset): string
    {
        return match ($preset) {
            'none' => __('None'), 'fade-up' => __('Fade up'), 'reveal-mask' => __('Reveal'), 'zoom-in' => __('Slow zoom'),
            'parallax-slow' => __('Parallax'), 'stagger-cards' => __('Cards one by one'), 'counter' => __('Counting numbers'),
            'line-draw' => __('Line drawing'), default => $preset,
        };
    }

    /** One tab per language; Arabic first in the Arabic dashboard, English first otherwise. */
    public static function localeTabs(\Closure $fields, ?string $label = null): Tabs
    {
        $order = app()->getLocale() === 'ar' ? ['ar', 'en', 'fr', 'hi', 'ur', 'zh'] : ['en', 'ar', 'fr', 'hi', 'ur', 'zh'];

        return Tabs::make($label ?? __('Languages'))->contained(false)->tabs(array_map(
            fn ($l) => Tab::make(Locales::ALL[$l]['native'])->extraAttributes(['dir' => Locales::ALL[$l]['dir'], 'lang' => Locales::ALL[$l]['tag']])
                ->schema($fields($l, $l === Locales::FALLBACK)),
            $order,
        ))->columnSpanFull();
    }

    public static function mediaSelect(string $name, string $label): Select
    {
        return Select::make($name)->label($label)
            ->options(fn () => MediaLibrary::options())->allowHtml()->searchable()->native(false)
            ->getSearchResultsUsing(fn (string $search) => collect(MediaLibrary::all())
                ->filter(fn ($m, $key) => str_contains(mb_strtolower($key.' '.implode(' ', $m['alt'] ?? [])), mb_strtolower($search)))
                ->keys()->take(40)->mapWithKeys(fn ($key) => [$key => MediaLibrary::options()[$key]])->all())
            ->helperText(fn ($state) => MediaLibrary::preview($state))
            ->live()
            ->createOptionForm([
                FileUpload::make('file')->label(__('Image'))->image()->required()->maxSize(12 * 1024)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('local')->directory('media-uploads')->visibility('private'),
                Fieldset::make(__('Description for screen readers and Google'))->schema([
                    TextInput::make('alt_en')->label('English')->required()->maxLength(160),
                    TextInput::make('alt_ar')->label('العربية')->maxLength(160),
                ]),
            ])
            ->createOptionModalHeading(__('Upload a new image'))
            ->createOptionUsing(function (array $data) {
                $path = storage_path('app/private/'.$data['file']);
                if (! is_file($path)) {
                    $path = storage_path('app/'.$data['file']);
                }
                $key = MediaLibrary::store($path, $data['alt_en'], $data['alt_ar'] ?? null);
                @unlink($path);

                return $key;
            });
    }
}
