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
 * The fixed wording of a template used by many records (every story, every case), per language. Stored in the
 * settings under `labels`; only what differs from the standard wording is kept, so anything left alone keeps
 * following it. Live when saved.
 */
abstract class EditFixedTexts extends Page
{
    /** Interface key => [dashboard label, long text?] */
    public const LABELS = [];

    /** Settings group holding this template's wording. */
    protected const GROUP = '';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament-panels::pages.page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('content.edit');
    }

    /** @return array<string, array<string, string>> key => locale => saved text */
    public static function saved(): array
    {
        return Setting::group(static::GROUP)['labels'] ?? [];
    }

    public function mount(): void
    {
        $saved = static::saved();
        $labels = [];
        foreach (array_keys(static::LABELS) as $key) {
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
                array_keys(static::LABELS), static::LABELS,
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
        $labels = [];
        foreach ($this->form->getState()['labels'] ?? [] as $key => $map) {
            foreach ((array) $map as $locale => $text) {
                $text = trim(strip_tags((string) $text));
                if ($text !== '' && isset(static::LABELS[$key]) && $text !== (UiStrings::forLocale($locale)[$key] ?? null)) {
                    $labels[$key][$locale] = $text;
                }
            }
        }
        Setting::updateOrCreate(['group' => static::GROUP, 'key' => 'labels'], ['value' => $labels]);
        Notification::make()->success()->title(__('Saved'))->send();
    }
}
