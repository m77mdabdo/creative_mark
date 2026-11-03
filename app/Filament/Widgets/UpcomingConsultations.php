<?php

namespace App\Filament\Widgets;

use App\Consultation\MailStrings;
use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** The next open consultations by date and time (Riyadh). */
class UpcomingConsultations extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('viewAny', ConsultationRequest::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Next consultations'))
            ->query(fn () => ConsultationRequest::query()->visibleTo(auth()->user())->holdingSlot()
                ->whereDate('preferred_date', '>=', now(config('consultation.timezone'))->toDateString())
                ->orderBy('preferred_date')->orderBy('preferred_slot')->with('assignee'))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('No upcoming consultations.'))
            ->recordUrl(fn (ConsultationRequest $r) => ConsultationRequestResource::getUrl('view', ['record' => $r]))
            ->columns([
                TextColumn::make('preferred_date')->label(__('When'))
                    ->formatStateUsing(fn ($state, ConsultationRequest $r) => $state->locale(app()->getLocale())->translatedFormat('D j M'))
                    ->description(fn (ConsultationRequest $r) => $r->preferred_slot.' · '.__('Riyadh time')),
                TextColumn::make('name')->label(__('Client'))->description(fn (ConsultationRequest $r) => $r->company),
                TextColumn::make('meeting_mode')->label(__('Meeting'))->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => MailStrings::get(app()->getLocale(), "mode_{$state}")),
                TextColumn::make('status')->label(__('Status'))->badge()
                    ->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
                TextColumn::make('assignee.name')->label(__('Assigned to'))->placeholder(__('Unassigned')),
            ]);
    }
}
