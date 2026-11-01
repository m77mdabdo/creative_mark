<?php

namespace App\Filament\Resources\Regions\Tables;

use App\Filament\Support\Columns;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order', fn () => (bool) auth()->user()?->can('content.publish'))
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                Columns::translated('name', __('Name')),
                Columns::translated('best_for', __('Best for')),
                Columns::status(),
                Columns::localeStatus(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
