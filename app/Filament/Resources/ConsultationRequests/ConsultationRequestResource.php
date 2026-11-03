<?php

namespace App\Filament\Resources\ConsultationRequests;

use App\Enums\ConsultationStatus;
use App\Filament\Resources\ConsultationRequests\Pages\EditConsultationRequest;
use App\Filament\Resources\ConsultationRequests\Pages\ListConsultationRequests;
use App\Filament\Resources\ConsultationRequests\Pages\ViewConsultationRequest;
use App\Filament\Resources\ConsultationRequests\Schemas\ConsultationRequestForm;
use App\Filament\Resources\ConsultationRequests\Schemas\ConsultationRequestInfolist;
use App\Filament\Resources\ConsultationRequests\Tables\ConsultationRequestsTable;
use App\Models\ConsultationRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConsultationRequestResource extends Resource
{
    protected static ?string $model = ConsultationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationGroup(): ?string
    {
        return __('Clients');
    }

    public static function getModelLabel(): string
    {
        return __('Consultation');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Consultations');
    }

    /** New bookings waiting for the team, shown on the menu. */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $count = $user ? static::getEloquentQuery()->where('status', ConsultationStatus::New)->count() : 0;

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        return $user ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return ConsultationRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ConsultationRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsultationRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsultationRequests::route('/'),
            'view' => ViewConsultationRequest::route('/{record}'),
            'edit' => EditConsultationRequest::route('/{record}/edit'),
        ];
    }
}
