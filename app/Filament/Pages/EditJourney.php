<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\KeepsSettingsAsDraft;
use App\Filament\Support\SectionForm;
use App\Models\Setting;
use App\Support\Locales;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The investor journey steps (presentation p39), shown in the "process" sections of the Services and
 * Start your company pages. Stored in the catalogue settings.
 */
class EditJourney extends Page
{
    use KeepsSettingsAsDraft;

    protected static ?string $slug = 'pages/journey';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament-panels::pages.page';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('content.edit');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Pages');
    }

    public static function getNavigationLabel(): string
    {
        return __('Investor journey steps');
    }

    public function getTitle(): string
    {
        return __('Investor journey steps');
    }

    protected function screenDescription(): string
    {
        return __('Shown on the Services and Start your company pages.');
    }

    protected function fillFromSettings(): void
    {
        $this->form->fill(['steps' => Setting::group('catalogue')['journey'] ?? []]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Repeater::make('steps')->hiddenLabel()
                ->schema([SectionForm::localeTabs(fn (string $l, bool $en) => [
                    TextInput::make("title.{$l}")->label(__('Title'))->required($en)->maxLength(60),
                    Textarea::make("text.{$l}")->label(__('Text'))->required($en)->rows(2)->maxLength(240),
                ])])
                ->minItems(2)->maxItems(12)->collapsible()->reorderableWithButtons()->cloneable()
                ->itemLabel(fn (array $state) => $state['title'][app()->getLocale()] ?? $state['title']['en'] ?? __('Step'))
                ->addActionLabel(__('Add a step')),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('save')]);
    }

    protected function settingWrites(array $state): array
    {
        $clean = fn (array $map) => array_filter(array_intersect_key(array_map(fn ($v) => is_string($v) ? trim(strip_tags($v)) : null, $map), Locales::ALL), 'filled');
        $steps = array_values(array_map(fn (array $s) => ['title' => $clean($s['title'] ?? []), 'text' => $clean($s['text'] ?? [])], $state['steps'] ?? []));

        return [['group' => 'catalogue', 'key' => 'journey', 'value' => $steps]];
    }

    protected function previewPath(): string
    {
        return '/start-your-company';
    }
}
