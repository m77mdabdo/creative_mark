<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\PublishedByStatus;
use App\Models\Contracts\Publishable;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model implements Publishable
{
    use HasTranslations, PublishedByStatus;

    protected $fillable = ['verified', 'consent_recorded_at', 'sort_order', 'status'];

    protected function casts(): array
    {
        return ['verified' => 'boolean', 'consent_recorded_at' => 'datetime'];
    }

    protected function translationModel(): string
    {
        return TestimonialTranslation::class;
    }

    public function sitePath(): string
    {
        return '/';
    }
}
