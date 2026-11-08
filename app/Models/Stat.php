<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Contracts\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Stat extends Model implements Publishable
{
    use HasTranslations;

    protected $fillable = ['key', 'value', 'icon', 'group', 'verified', 'source', 'source_url', 'sort_order'];

    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    protected function translationModel(): string
    {
        return StatTranslation::class;
    }

    public function sitePath(): string
    {
        return '/';
    }

    /** RULE-01: only verified figures are shown. */
    public function isLive(): bool
    {
        return $this->verified;
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('verified', true);
    }
}
