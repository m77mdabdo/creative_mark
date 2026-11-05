<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticleTranslation extends Model
{
    protected $fillable = ['locale', 'title', 'excerpt', 'body', 'takeaway', 'seo_title', 'seo_description', 'fact_values', 'fact_labels', 'review_status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class, 'reviewed_at' => 'datetime', 'fact_values' => 'array', 'fact_labels' => 'array'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'news_article_id');
    }
}
