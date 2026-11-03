<?php

namespace App\Filament\Resources\ConsultationRequests\Schemas;

use App\Enums\ConsultationStatus;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** The team edits only the workflow fields; what the client submitted is never changed. */
class ConsultationRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Follow-up'))->columns(2)->schema([
                Select::make('status')->label(__('Status'))->required()
                    ->options(collect(ConsultationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Select::make('assigned_to')->label(__('Assigned to'))->searchable()
                    ->options(fn () => User::query()->where('is_active', true)->orderBy('name')->get()
                        ->filter(fn (User $u) => $u->can('consultations.update'))->pluck('name', 'id'))
                    ->visible(fn () => (bool) auth()->user()?->can('leads.assign')),
                Textarea::make('internal_notes')->label(__('Internal notes'))->rows(5)->maxLength(5000)->columnSpanFull()
                    ->helperText(__('Visible to the team only. Never sent to the client.')),
            ]),
        ]);
    }
}
