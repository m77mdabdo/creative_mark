<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Content visitors can see. `scopeLive` is the one definition of "on the website" used by the site and by the
 * dashboard: changes to a live record wait as a draft until they are published.
 */
interface Publishable
{
    public function isLive(): bool;

    public function scopeLive(Builder $query): Builder;

    /** Where the record appears on the site, without the language prefix (for previews). */
    public function sitePath(): string;
}
