<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTranslation extends Model
{
    protected $fillable = ['locale', 'title', 'tagline', 'summary', 'cta', 'review_status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
