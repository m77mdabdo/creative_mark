<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Actions\TranslateMissingLanguagesAction;
use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Testimonials\TestimonialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTestimonial extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [...$this->draftActions(), TranslateMissingLanguagesAction::make(), DeleteAction::make()];
    }
}
