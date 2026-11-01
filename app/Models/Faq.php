<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasTranslations;

    protected $fillable = ['category', 'sort_order', 'status', 'show_on_home', 'needs_review'];

    protected function casts(): array
    {
        return ['show_on_home' => 'boolean', 'needs_review' => 'boolean'];
    }

    protected function translationModel(): string
    {
        return FaqTranslation::class;
    }
}
