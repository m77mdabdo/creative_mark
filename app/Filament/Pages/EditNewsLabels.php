<?php

namespace App\Filament\Pages;

use App\Filament\Support\SectionForm;
use App\Models\Setting;
use App\Support\Locales;
use App\Translation\UiStrings;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

/**
 * The fixed wording on every story's page ("Related news", "Read the original report", the closing invitation…),
 * per language. Stored in the news settings; anything left empty uses the standard wording. Live when saved.
 */
class EditNewsLabels extends Page
{
    /** Interface key => [dashboard label, long text?] */
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

    protected static ?string $slug = 'pages/news/story-texts';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament-panels::pages.page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('content.edit');
    }

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

    /** @return array<string, array<string, string>> key => locale => saved text */
    public static function saved(): array
    {
        return Setting::group('news')['labels'] ?? [];
    }

    public function mount(): void
    {
        $saved = self::saved();
        $labels = [];
        foreach (array_keys(self::LABELS) as $key) {
            foreach (Locales::codes() as $locale) {
                $labels[$key][$locale] = $saved[$key][$locale] ?? UiStrings::forLocale($locale)[$key] ?? null;
            }
        }
        $this->form->fill(['labels' => $labels]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            SectionForm::localeTabs(fn (string $l) => array_map(
                fn (string $key, array $meta) => ($meta[1] ? Textarea::make("labels.{$key}.{$l}")->rows(2)->maxLength(400) : TextInput::make("labels.{$key}.{$l}")->maxLength(120))->label(__($meta[0])),
                array_keys(self::LABELS), self::LABELS,
            )),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('save')]);
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('save')->label(__('Save'))->icon('heroicon-o-check')->keyBindings(['mod+s'])->action(fn () => $this->save())];
    }

    public function save(): void
    {
        // Only what differs from the standard wording is stored, so future wording updates still reach the rest.
        $labels = [];
        foreach ($this->form->getState()['labels'] ?? [] as $key => $map) {
            foreach ((array) $map as $locale => $text) {
                $text = trim(strip_tags((string) $text));
                if ($text !== '' && isset(self::LABELS[$key]) && $text !== (UiStrings::forLocale($locale)[$key] ?? null)) {
                    $labels[$key][$locale] = $text;
                }
            }
        }
        Setting::updateOrCreate(['group' => 'news', 'key' => 'labels'], ['value' => $labels]);
        Notification::make()->success()->title(__('Saved'))->send();
    }
}
