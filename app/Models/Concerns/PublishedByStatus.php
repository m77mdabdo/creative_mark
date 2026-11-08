<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** For App\Models\Contracts\Publishable records whose `status` column decides whether visitors see them. */
trait PublishedByStatus
{
    public function isLive(): bool
    {
        return $this->status === 'published';
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), 'published');
    }
}
