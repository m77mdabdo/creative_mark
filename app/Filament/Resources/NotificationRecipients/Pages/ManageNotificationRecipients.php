<?php

namespace App\Filament\Resources\NotificationRecipients\Pages;

use App\Filament\Resources\NotificationRecipients\NotificationRecipientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNotificationRecipients extends ManageRecords
{
    protected static string $resource = NotificationRecipientResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
