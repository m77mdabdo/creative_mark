<?php

namespace App\Filament\Resources\NotificationRecipients;

use App\Filament\Resources\NotificationRecipients\Pages\ManageNotificationRecipients;
use App\Models\NotificationRecipient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** The email addresses that receive every new consultation booking. */
class NotificationRecipientResource extends Resource
{
    protected static ?string $model = NotificationRecipient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Clients');
    }

    public static function getModelLabel(): string
    {
        return __('Booking email recipient');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Booking email recipients');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(120),
            TextInput::make('email')->label(__('Email'))->required()->email()->maxLength(190)->unique(ignoreRecord: true),
            Toggle::make('active')->label(__('Receives booking emails'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable(),
                TextColumn::make('email')->label(__('Email'))->searchable()->copyable(),
                ToggleColumn::make('active')->label(__('Active')),
                TextColumn::make('last_notified_at')->label(__('Last email'))->since()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('test')->label(__('Send test'))->icon('heroicon-o-envelope')->color('gray')
                    ->requiresConfirmation()
                    ->action(function (NotificationRecipient $record) {
                        try {
                            Mail::raw(__('This is a test from the Creative Mark dashboard. Booking emails will arrive at this address.'),
                                fn ($m) => $m->to($record->email, $record->name)->subject('Creative Mark · '.__('Test email')));
                            Notification::make()->success()->title(__('Test email sent to :email', ['email' => $record->email]))->send();
                        } catch (Throwable $e) {
                            Notification::make()->danger()->title(__('Sending failed'))->body($e->getMessage())->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageNotificationRecipients::route('/')];
    }
}
