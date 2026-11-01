<?php

namespace App\Filament\Resources\Services\Tables;

use App\Filament\Support\Columns;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServicesTable
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
                TextColumn::make('slug')->label(__('Slug'))->searchable()->toggleable(),
                Columns::status(),
                Columns::localeStatus(),
                TextColumn::make('updated_at')->label(__('Updated'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => __('Draft'), 'published' => __('Published')]),
            ])
            ->recordActions([EditAction::make()]);
    }
}
