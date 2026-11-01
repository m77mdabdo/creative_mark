<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestimonialTranslation extends Model
{
    protected $fillable = ['locale', 'quote', 'author', 'context', 'review_status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function testimonial(): BelongsTo
    {
        return $this->belongsTo(Testimonial::class, 'testimonial_id');
    }
}
