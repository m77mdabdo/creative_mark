<?php

namespace App\Filament\Resources\ConsultationRequests\Tables;

use App\Enums\ConsultationStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConsultationRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('assignee'))
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->columns([
                TextColumn::make('reference')->label(__('Reference'))->searchable()->fontFamily('mono')->copyable(),
                TextColumn::make('name')->label(__('Client'))->searchable()->description(fn ($record) => $record->email),
                TextColumn::make('preferred_date')->label(__('Preferred time'))->date()->sortable()
                    ->description(fn ($record) => $record->preferred_slot.' (Riyadh)'),
                TextColumn::make('meeting_mode')->label(__('Meeting'))->badge()->color('gray'),
                TextColumn::make('status')->label(__('Status'))->badge()
                    ->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
                TextColumn::make('assignee.name')->label(__('Assigned to'))->placeholder('—'),
                IconColumn::make('notified_at')->label(__('Emailed'))->boolean()->getStateUsing(fn ($record) => $record->notified_at !== null && $record->mail_error === null),
                TextColumn::make('created_at')->label(__('Received'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Status'))
                    ->options(collect(ConsultationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }
}
