<?php

namespace App\Filament\Resources\Faqs\Tables;

use App\Filament\Support\Columns;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order', fn () => (bool) auth()->user()?->can('content.publish'))
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                Columns::translated('question', __('Question')),
                TextColumn::make('category')->label(__('Category'))->badge(),
                IconColumn::make('show_on_home')->label(__('Home'))->boolean(),
                IconColumn::make('needs_review')->label(__('Needs review'))->boolean()->trueColor('warning'),
                Columns::status(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
