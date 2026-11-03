<?php

namespace App\Filament\Resources\ConsultationRequests\Pages;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditConsultationRequest extends EditRecord
{
    protected static string $resource = ConsultationRequestResource::class;

    /** Only workflow fields can change; assignment needs leads.assign. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = array_intersect_key($data, array_flip(['status', 'assigned_to', 'internal_notes']));
        if (! auth()->user()?->can('leads.assign')) {
            unset($data['assigned_to']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ConsultationRequestResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
