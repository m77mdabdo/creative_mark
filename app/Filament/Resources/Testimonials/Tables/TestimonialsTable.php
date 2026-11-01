<?php

namespace App\Filament\Resources\Testimonials\Tables;

use App\Filament\Support\Columns;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order', fn () => (bool) auth()->user()?->can('content.publish'))
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                Columns::translated('quote', __('Quote')),
                TextColumn::make('consent_recorded_at')->label(__('Consent'))->date()->placeholder(__('None')),
                Columns::status(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
