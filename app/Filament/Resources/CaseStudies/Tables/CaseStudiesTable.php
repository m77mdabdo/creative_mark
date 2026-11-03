<?php

namespace App\Filament\Resources\CaseStudies\Tables;

use App\Filament\Support\Columns;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CaseStudiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order', fn () => (bool) auth()->user()?->can('content.publish'))
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                Columns::translated('title', __('Title')),
                TextColumn::make('sector')->label(__('Sector'))->badge(),
                TextColumn::make('origin')->label(__('Country'))->formatStateUsing(fn (string $state) => strtoupper($state)),
                IconColumn::make('illustrative')->label(__('Illustrative'))->boolean()->trueColor('warning'),
                Columns::localeStatus(),
                Columns::status(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
