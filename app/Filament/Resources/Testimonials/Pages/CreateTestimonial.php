<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Concerns\SavesTranslations;
use App\Filament\Resources\Testimonials\TestimonialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestimonial extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = TestimonialResource::class;
}
