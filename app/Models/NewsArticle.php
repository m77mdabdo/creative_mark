<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    use HasTranslations;

    /** Newsroom sections; labels live in lang/ui (newsCat_*). */
    public const CATEGORIES = ['economy', 'regulation', 'real-estate', 'tourism', 'tax', 'zones'];

    /** @return array<string, string> category => name in the dashboard's language */
    public static function categoryLabels(): array
    {
        return [
            'economy' => __('Economy'), 'regulation' => __('Regulation'), 'real-estate' => __('Real estate'),
            'tourism' => __('Tourism'), 'tax' => __('Tax'), 'zones' => __('Economic zones'),
        ];
    }

    protected $fillable = ['slug', 'category', 'media', 'published_on', 'source_name', 'source_url', 'featured', 'status', 'publish_at'];

    protected $attributes = ['featured' => false, 'status' => 'draft'];

    protected function casts(): array
    {
        return ['published_on' => 'date', 'publish_at' => 'datetime', 'live_notified_at' => 'datetime', 'featured' => 'boolean'];
    }

    /** Published articles, newest first. */
    protected static function booted(): void
    {
        // A story moved to a new time is announced again when that time comes.
        static::saving(function (self $story) {
            if ($story->isDirty('publish_at')) {
                $story->live_notified_at = null;
            }
        });
    }

    /** Published, but waiting for its publish time. */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('publish_at', '>', now());
    }

    public function isScheduled(): bool
    {
        return $this->status === 'published' && $this->publish_at?->isFuture();
    }

    public function scopeLive(Builder $query): Builder
    {
        // A story with a future publish time stays off the site until that moment, with no background job needed.
        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->orderByRaw('COALESCE(publish_at, published_on) DESC')->orderByDesc('id');
    }

    protected function translationModel(): string
    {
        return NewsArticleTranslation::class;
    }
}
