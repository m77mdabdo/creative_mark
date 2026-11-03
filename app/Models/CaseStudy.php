<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'sector', 'origin', 'region', 'media', 'services', 'illustrative', 'consent_recorded_at', 'sort_order', 'status'];

    protected $attributes = ['illustrative' => true, 'status' => 'draft', 'sort_order' => 0];

    protected function casts(): array
    {
        return ['services' => 'array', 'illustrative' => 'boolean', 'consent_recorded_at' => 'datetime'];
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'published')->orderBy('sort_order')->orderBy('id');
    }

    protected function translationModel(): string
    {
        return CaseStudyTranslation::class;
    }
}
