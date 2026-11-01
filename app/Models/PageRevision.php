<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Immutable snapshot written on every publish (ADR-0004). */
class PageRevision extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['page_id', 'sections', 'kind', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['sections' => 'array'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
