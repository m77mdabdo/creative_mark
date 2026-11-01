<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryTranslation extends Model
{
    protected $fillable = ['locale', 'name', 'review_status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
