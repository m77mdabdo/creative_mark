<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasTranslations;

    protected $fillable = ['code', 'sort_order', 'status'];

    protected function translationModel(): string
    {
        return CountryTranslation::class;
    }
}
