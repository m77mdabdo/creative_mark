<?php

namespace App\Filament\Resources\NewsArticles\Tables;

use App\Filament\Support\Columns;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NewsArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->defaultSort('published_on', 'desc')
            ->columns([
                TextColumn::make('published_on')->label(__('Date'))->date()->sortable(),
                Columns::translated('title', __('Headline')),
                TextColumn::make('category')->label(__('Category'))->badge(),
                TextColumn::make('source_name')->label(__('Source')),
                IconColumn::make('featured')->label(__('Lead'))->boolean(),
                Columns::localeStatus(),
                Columns::status(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
