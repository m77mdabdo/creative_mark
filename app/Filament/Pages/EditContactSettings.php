<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Phone, email, WhatsApp and social links shown in the footer, the contact page and the WhatsApp button. */
class EditContactSettings extends Page
{
    protected static ?string $slug = 'settings/contact';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?int $navigationSort = 1;

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
        return __('Contact details');
    }

    public function getTitle(): string
    {
        return __('Contact details');
    }

    public function getSubheading(): string
    {
        return __('Shown in the footer, on the contact page and in the WhatsApp button. Changes appear when you save.');
    }

    public function mount(): void
    {
        $contact = Setting::group('contact');
        $this->form->fill([
            'phone' => $contact['phone'] ?? null, 'email' => $contact['email'] ?? null, 'whatsapp' => $contact['whatsapp'] ?? null,
            'social' => Setting::group('social')['links'] ?? [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $phone = ['regex:/^\+[1-9][0-9 ]{7,18}$/'];

        return $schema->statePath('data')->components([
            Section::make(__('Contact'))->columns(['default' => 1, 'md' => 3])->schema([
                TextInput::make('whatsapp')->label('WhatsApp')->tel()->rules($phone)->maxLength(24)->extraInputAttributes(['dir' => 'ltr'])
                    ->placeholder('+966 5X XXX XXXX')->helperText(__('International format with the country code. Leave empty to hide the WhatsApp button.')),
                TextInput::make('phone')->label(__('Phone'))->tel()->rules($phone)->maxLength(24)->extraInputAttributes(['dir' => 'ltr'])->placeholder('+966 11 XXX XXXX'),
                TextInput::make('email')->label(__('Email'))->email()->maxLength(190)->extraInputAttributes(['dir' => 'ltr']),
            ]),
            Section::make(__('Social media'))->columns(['default' => 1, 'md' => 3])->schema([
                TextInput::make('social.instagram')->label('Instagram')->url()->maxLength(300)->extraInputAttributes(['dir' => 'ltr'])->startsWith(['https://']),
                TextInput::make('social.facebook')->label('Facebook')->url()->maxLength(300)->extraInputAttributes(['dir' => 'ltr'])->startsWith(['https://']),
                TextInput::make('social.linkedin')->label('LinkedIn')->url()->maxLength(300)->extraInputAttributes(['dir' => 'ltr'])->startsWith(['https://']),
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
        foreach (['phone', 'email', 'whatsapp'] as $key) {
            Setting::updateOrCreate(['group' => 'contact', 'key' => $key], ['value' => filled($data[$key] ?? null) ? trim($data[$key]) : null]);
        }
        Setting::updateOrCreate(['group' => 'social', 'key' => 'links'], ['value' => array_map(fn ($v) => filled($v) ? trim($v) : null, $data['social'] ?? [])]);
        Notification::make()->success()->title(__('Saved'))->send();
    }
}
