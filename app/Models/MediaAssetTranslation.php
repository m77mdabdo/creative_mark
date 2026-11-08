<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAssetTranslation extends Model
{
    protected $fillable = ['locale', 'alt', 'review_status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
