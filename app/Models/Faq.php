<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Contracts\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model implements Publishable
{
    use HasTranslations;

    protected $fillable = ['category', 'sort_order', 'status', 'show_on_home', 'needs_review'];

    protected function casts(): array
    {
        return ['show_on_home' => 'boolean', 'needs_review' => 'boolean'];
    }

    protected function translationModel(): string
    {
        return FaqTranslation::class;
    }

    public function sitePath(): string
    {
        return '/faq';
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && ! $this->needs_review;
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('needs_review', false);
    }
}
