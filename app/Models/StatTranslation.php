<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatTranslation extends Model
{
    protected $fillable = ['locale', 'label', 'review_status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function stat(): BelongsTo
    {
        return $this->belongsTo(Stat::class, 'stat_id');
    }
}
