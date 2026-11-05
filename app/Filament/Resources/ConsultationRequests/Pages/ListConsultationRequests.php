<?php

namespace App\Filament\Resources\ConsultationRequests\Pages;

use App\Audit\Audit;
use App\Consultation\BookingExport;
use App\Enums\ConsultationStatus;
use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListConsultationRequests extends ListRecords
{
    protected static string $resource = ConsultationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label(__('Export to Excel'))->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->visible(fn () => (bool) auth()->user()?->can('leads.export'))
                ->modalHeading(__('Export bookings'))->modalDescription(__('The file holds clients’ personal data: keep it safe and do not forward it.'))
                ->modalSubmitActionLabel(__('Download'))
                ->schema([
                    DatePicker::make('from')->label(__('Received from')),
                    DatePicker::make('until')->label(__('Received until'))->afterOrEqual('from'),
                    Select::make('statuses')->label(__('Status'))->multiple()->native(false)->placeholder(__('All'))
                        ->options(collect(ConsultationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                ])
                ->action(function (array $data) {
                    abort_unless(auth()->user()?->can('leads.export'), 403);
                    $tz = config('consultation.timezone');
                    $query = ConsultationRequestResource::getEloquentQuery()
                        ->when($data['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', now($tz)->parse($v, $tz)->startOfDay()->utc()))
                        ->when($data['until'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', now($tz)->parse($v, $tz)->endOfDay()->utc()))
                        ->when($data['statuses'] ?? null, fn ($q, $v) => $q->whereIn('status', $v));
                    [$path, $count] = BookingExport::toFile($query);
                    if ($count === 0) {
                        @unlink($path);
                        Notification::make()->warning()->title(__('No bookings match these choices.'))->send();

                        return null;
                    }
                    Audit::record('exported', null, __(':count bookings', ['count' => $count]), type: 'ConsultationRequest');

                    return response()->download($path, 'creative-mark-consultations-'.now($tz)->format('Y-m-d').'.xlsx', [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])->deleteFileAfterSend();
                }),
        ];
    }
}
