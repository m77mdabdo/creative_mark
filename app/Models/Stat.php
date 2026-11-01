<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    use HasTranslations;

    protected $fillable = ['key', 'value', 'icon', 'group', 'verified', 'source', 'source_url', 'sort_order'];

    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    protected function translationModel(): string
    {
        return StatTranslation::class;
    }
}
