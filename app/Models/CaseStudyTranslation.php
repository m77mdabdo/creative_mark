<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStudyTranslation extends Model
{
    protected $fillable = ['locale', 'title', 'client', 'entity', 'summary', 'challenge', 'step_titles', 'step_texts', 'delivered', 'outcome', 'review_status', 'reviewed_at'];

    protected function casts(): array
    {
        return [
            'review_status' => ReviewStatus::class, 'reviewed_at' => 'datetime',
            'step_titles' => 'array', 'step_texts' => 'array', 'delivered' => 'array',
        ];
    }

    public function caseStudy(): BelongsTo
    {
        return $this->belongsTo(CaseStudy::class);
    }
}
