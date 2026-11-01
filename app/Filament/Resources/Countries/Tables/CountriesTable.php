<?php

namespace App\Filament\Resources\Countries\Tables;

use App\Filament\Support\Columns;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order', fn () => (bool) auth()->user()?->can('content.publish'))
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('code')->label(__('Code'))->badge(),
                Columns::translated('name', __('Name')),
                Columns::status(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
