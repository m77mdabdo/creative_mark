<?php

namespace App\Filament\Resources\CaseStudies\Tables;

use App\Filament\Support\Columns;
use App\Models\Setting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
                TextColumn::make('sector')->label(__('Sector'))->badge()->formatStateUsing(fn (string $state) => Setting::group('catalogue')['industries'][$state][app()->getLocale()] ?? $state),
                TextColumn::make('origin')->label(__('Country'))->formatStateUsing(fn (string $state) => strtoupper($state)),
                IconColumn::make('illustrative')->label(__('Illustrative'))->boolean()->trueColor('warning'),
                Columns::localeStatus(),
                Columns::status(),
            ])
            ->filters([
                SelectFilter::make('sector')->label(__('Sector'))->options(fn () => collect(Setting::group('catalogue')['industries'] ?? [])->map(fn ($l) => $l[app()->getLocale()] ?? $l['en'])->all()),
                SelectFilter::make('status')->label(__('Status'))->options(fn () => ['draft' => __('Draft'), 'published' => __('Published')]),
                TernaryFilter::make('illustrative')->label(__('Illustrative example')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
