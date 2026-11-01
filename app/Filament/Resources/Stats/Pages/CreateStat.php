<?php

namespace App\Filament\Resources\Stats\Pages;

use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Stats\StatResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStat extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = StatResource::class;
}
