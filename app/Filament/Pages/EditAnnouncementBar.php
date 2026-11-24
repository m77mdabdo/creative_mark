<?php

namespace App\Filament\Pages;

use App\Content\SectionSchema;
use App\Filament\Concerns\KeepsSettingsAsDraft;
use App\Filament\Support\SectionForm;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** The scrolling bar above the menu on every page: on or off, its speed, its badge and its messages per language. */
class EditAnnouncementBar extends Page
{
    use KeepsSettingsAsDraft;

    private const MAX_MESSAGES = 12;

    protected static ?string $slug = 'settings/announcement';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 3;

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
        return __('Announcement bar');
    }

    public function getTitle(): string
    {
        return __('Announcement bar');
    }

    protected function screenDescription(): string
    {
        return __('The scrolling bar above the menu on every page.');
    }

    protected function fillFromSettings(): void
    {
        $bar = Setting::group('announcement');
        $this->form->fill([
            'enabled' => (bool) ($bar['enabled'] ?? false),
            'speed' => $bar['speed'] ?? 'normal',
            'label' => $bar['label'] ?? [],
            'items' => $bar['items'] ?? [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $href = fn () => function (string $attribute, $value, $fail) {
            if (filled($value) && ! SectionSchema::safeHref((string) $value)) {
                $fail(__('Use /page, #section, https://, mailto: or tel: links only.'));
            }
        };

        return $schema->statePath('data')->components([
            Section::make(__('Display'))->columns(['default' => 1, 'md' => 2])->schema([
                Toggle::make('enabled')->label(__('Show the bar on every page'))->live(),
                Select::make('speed')->label(__('Speed'))->native(false)->selectablePlaceholder(false)->required()
                    ->options(['slow' => __('Slow'), 'normal' => __('Normal'), 'fast' => __('Fast')])
                    ->in(HandleInertiaRequests::ANNOUNCEMENT_SPEEDS),
                SectionForm::localeTabs(fn (string $l) => [TextInput::make("label.{$l}")->label(__('Badge text'))->maxLength(30)
                    ->helperText(__('The short gold label at the start of the bar. Leave empty to hide it.'))]),
            ]),
            Section::make(__('Messages'))->description(__('Shown one after another, in this order. A message without a link is plain text.'))->schema([
                Repeater::make('items')->label(__('Messages'))->hiddenLabel()->reorderableWithButtons()->collapsible()->maxItems(self::MAX_MESSAGES)
                    ->required(fn (Get $get) => (bool) $get('enabled'))->minItems(fn (Get $get) => $get('enabled') ? 1 : 0)
                    ->itemLabel(fn (array $state) => $state['text'][app()->getLocale()] ?? $state['text']['en'] ?? null)
                    ->addActionLabel(__('Add a message'))->deleteAction(fn ($action) => $action->requiresConfirmation())
                    ->schema([
                        TextInput::make('href')->label(__('Link (optional)'))->maxLength(300)->extraInputAttributes(['dir' => 'ltr'])->rule($href),
                        SectionForm::localeTabs(fn (string $l, bool $en) => [TextInput::make("text.{$l}")->label(__('Message'))->required($en)->maxLength(160)]),
                    ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('save')]);
    }

    protected function settingWrites(array $state): array
    {
        $clean = fn ($m) => array_filter(array_map(fn ($t) => is_string($t) ? trim(strip_tags($t)) : null, (array) $m), 'filled');
        $items = array_values(array_filter(array_map(fn (array $i) => array_filter([
            'href' => trim((string) ($i['href'] ?? '')),
            'text' => $clean($i['text'] ?? []),
        ]), $state['items'] ?? []), fn (array $i) => ! empty($i['text'])));

        return [
            ['group' => 'announcement', 'key' => 'enabled', 'value' => (bool) ($state['enabled'] ?? false)],
            ['group' => 'announcement', 'key' => 'speed', 'value' => $state['speed'] ?? 'normal'],
            ['group' => 'announcement', 'key' => 'label', 'value' => $clean($state['label'] ?? [])],
            ['group' => 'announcement', 'key' => 'items', 'value' => $items],
        ];
    }

    protected function previewPath(): string
    {
        return '/';
    }

    protected function canPublish(): bool
    {
        return static::canAccess();
    }
}
