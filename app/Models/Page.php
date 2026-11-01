<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasTranslations;

    protected $fillable = ['key', 'status', 'draft_sections', 'published_revision_id', 'lock_version', 'published_at'];

    protected function casts(): array
    {
        return ['draft_sections' => 'array', 'published_at' => 'datetime'];
    }

    protected function translationModel(): string
    {
        return PageTranslation::class;
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class);
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(PageRevision::class, 'published_revision_id');
    }
}
