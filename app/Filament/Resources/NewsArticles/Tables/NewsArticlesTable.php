<?php

namespace App\Filament\Resources\NewsArticles\Tables;

use App\Filament\Support\Columns;
use App\Models\NewsArticle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                TextColumn::make('category')->label(__('Category'))->badge()->formatStateUsing(fn (string $state) => NewsArticle::categoryLabels()[$state] ?? $state),
                TextColumn::make('source_name')->label(__('Source')),
                IconColumn::make('featured')->label(__('Lead'))->boolean(),
                Columns::localeStatus(),
                Columns::status(),
            ])
            ->filters([
                SelectFilter::make('category')->label(__('Category'))->options(fn () => NewsArticle::categoryLabels()),
                SelectFilter::make('status')->label(__('Status'))->options(fn () => ['draft' => __('Draft'), 'published' => __('Published')]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
