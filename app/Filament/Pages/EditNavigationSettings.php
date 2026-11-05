<?php

namespace App\Filament\Pages;

use App\Content\SectionSchema;
use App\Filament\Support\SectionForm;
use App\Models\Setting;
use App\Support\Locales;
use App\Translation\UiStrings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** The site's top menu, its main button, the offices and the footer wording, per language. Live when saved. */
class EditNavigationSettings extends Page
{
    /** Footer and menu wording stored as interface overrides. */
    private const FOOTER_KEYS = ['tagline', 'follow', 'company', 'solutions', 'allServices', 'offices', 'rights', 'mapCredit', 'photoNote', 'privacyLink', 'termsLink', 'contactLink', 'faqLink', 'startCompany', 'startCompanyText'];

    protected static ?string $slug = 'settings/navigation';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament-panels::pages.page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('settings.general');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Menu and footer');
    }

    public function getTitle(): string
    {
        return __('Menu and footer');
    }

    public function getSubheading(): string
    {
        return __('The top menu, its main button, the offices and the footer. Changes appear on the website when you save.');
    }

    public function mount(): void
    {
        $nav = Setting::group('navigation');
        $saved = Setting::group('ui')['labels'] ?? [];
        $footer = [];
        foreach (self::FOOTER_KEYS as $key) {
            foreach (Locales::codes() as $locale) {
                $footer[$key][$locale] = $saved[$key][$locale] ?? UiStrings::standard($locale)[$key] ?? null;
            }
        }
        $this->form->fill([
            'header' => array_map(fn ($n) => [...$n, 'mega' => ! empty($n['mega'])], $nav['header'] ?? []),
            'cta' => $nav['cta'] ?? [],
            'offices' => Setting::group('contact')['offices'] ?? [],
            'footer' => $footer,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $href = fn () => function (string $attribute, $value, $fail) {
            if (! SectionSchema::safeHref((string) $value)) {
                $fail(__('Use /page, #section, https://, mailto: or tel: links only.'));
            }
        };
        $standard = UiStrings::standard(app()->getLocale());

        return $schema->statePath('data')->components([
            Section::make(__('Top menu'))->collapsible()->schema([
                Repeater::make('header')->hiddenLabel()->reorderableWithButtons()->collapsible()->collapsed()->maxItems(10)
                    ->itemLabel(fn (array $state) => $state['label'][app()->getLocale()] ?? $state['label']['en'] ?? null)
                    ->addActionLabel(__('Add a menu item'))->deleteAction(fn ($action) => $action->requiresConfirmation())
                    ->schema([
                        TextInput::make('href')->label(__('Link'))->required()->maxLength(300)->extraInputAttributes(['dir' => 'ltr'])->rule($href),
                        Toggle::make('mega')->label(__('Opens the solutions menu')),
                        SectionForm::localeTabs(fn (string $l, bool $en) => [TextInput::make("label.{$l}")->label(__('Name'))->required($en)->maxLength(60)]),
                    ]),
            ]),
            Section::make(__('Main button'))->collapsible()->collapsed()->schema([
                TextInput::make('cta.href')->label(__('Link'))->required()->maxLength(300)->extraInputAttributes(['dir' => 'ltr'])->rule($href),
                SectionForm::localeTabs(fn (string $l, bool $en) => [TextInput::make("cta.label.{$l}")->label(__('Button text'))->required($en)->maxLength(60)]),
            ]),
            Section::make(__('Offices'))->collapsible()->collapsed()->schema([
                Repeater::make('offices')->hiddenLabel()->reorderableWithButtons()->collapsible()->collapsed()->maxItems(6)
                    ->itemLabel(fn (array $state) => $state['country'][app()->getLocale()] ?? $state['country']['en'] ?? null)
                    ->addActionLabel(__('Add an office'))->deleteAction(fn ($action) => $action->requiresConfirmation())
                    ->schema([
                        Hidden::make('key'),
                        SectionForm::localeTabs(fn (string $l, bool $en) => [
                            TextInput::make("country.{$l}")->label(__('Country'))->required($en)->maxLength(80),
                            Textarea::make("address.{$l}")->label(__('Street address'))->required($en)->rows(2)->maxLength(240),
                        ]),
                    ]),
            ]),
            Section::make(__('Footer and menu wording'))->collapsible()->collapsed()->schema([
                SectionForm::localeTabs(fn (string $l) => array_map(
                    fn (string $key) => TextInput::make("footer.{$key}.{$l}")->label($standard[$key] ?? $key)->maxLength(200),
                    self::FOOTER_KEYS,
                )),
            ]),
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
        $data = $this->form->getState();
        $map = fn ($m) => array_filter(array_map(fn ($t) => is_string($t) ? trim(strip_tags($t)) : null, (array) $m), 'filled');
        // Only one item opens the solutions menu; it keeps its original value.
        $header = array_values(array_map(fn ($n) => array_filter(['label' => $map($n['label'] ?? []), 'href' => trim((string) $n['href']), 'mega' => ! empty($n['mega']) ? 'services' : null]), $data['header'] ?? []));
        Setting::updateOrCreate(['group' => 'navigation', 'key' => 'header'], ['value' => $header]);
        Setting::updateOrCreate(['group' => 'navigation', 'key' => 'cta'], ['value' => ['label' => $map($data['cta']['label'] ?? []), 'href' => trim((string) ($data['cta']['href'] ?? '/consultation'))]]);
        Setting::updateOrCreate(['group' => 'contact', 'key' => 'offices'], ['value' => array_values(array_map(fn ($o, $i) => [
            'key' => $o['key'] ?? 'office-'.($i + 1), 'country' => $map($o['country'] ?? []), 'address' => $map($o['address'] ?? []),
        ], $data['offices'] ?? [], array_keys($data['offices'] ?? [])))]);

        $labels = array_diff_key(Setting::group('ui')['labels'] ?? [], array_flip(self::FOOTER_KEYS));
        foreach ($data['footer'] ?? [] as $key => $texts) {
            foreach ((array) $texts as $locale => $text) {
                $text = trim(strip_tags((string) $text));
                if ($text !== '' && in_array($key, self::FOOTER_KEYS, true) && $text !== (UiStrings::standard($locale)[$key] ?? null)) {
                    $labels[$key][$locale] = $text;
                }
            }
        }
        Setting::updateOrCreate(['group' => 'ui', 'key' => 'labels'], ['value' => $labels]);
        Notification::make()->success()->title(__('Saved'))->send();
    }
}
