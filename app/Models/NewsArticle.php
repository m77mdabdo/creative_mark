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

    protected $fillable = ['slug', 'category', 'media', 'published_on', 'source_name', 'source_url', 'featured', 'status'];

    protected $attributes = ['featured' => false, 'status' => 'draft'];

    protected function casts(): array
    {
        return ['published_on' => 'date', 'featured' => 'boolean'];
    }

    /** Published articles, newest first. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'published')->orderByDesc('published_on')->orderByDesc('id');
    }

    protected function translationModel(): string
    {
        return NewsArticleTranslation::class;
    }
}
