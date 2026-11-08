<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\PublishedByStatus;
use App\Models\Contracts\Publishable;
use Illuminate\Database\Eloquent\Model;

class Country extends Model implements Publishable
{
    use HasTranslations, PublishedByStatus;

    protected $fillable = ['code', 'sort_order', 'status'];

    protected function translationModel(): string
    {
        return CountryTranslation::class;
    }

    public function sitePath(): string
    {
        return '/';
    }
}
