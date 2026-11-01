<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'icon', 'media', 'sort_order', 'status', 'needs_review'];

    protected function casts(): array
    {
        return ['needs_review' => 'boolean'];
    }

    protected function translationModel(): string
    {
        return ServiceTranslation::class;
    }
}
