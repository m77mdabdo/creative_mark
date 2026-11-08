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

    /** Where the page is on the site, without the language prefix. */
    public function sitePath(): string
    {
        return match (true) {
            $this->key === 'home' => '/',
            str_starts_with($this->key, 'service-') => '/services/'.substr($this->key, 8),
            str_starts_with($this->key, 'region-') => '/regions/'.substr($this->key, 7),
            default => '/'.$this->key,
        };
    }

    /** The page editor's address inside the dashboard (App\Filament\Pages\Edit*Page). */
    public function editorPath(): string
    {
        return '/'.trim(config('admin.path'), '/').'/pages'.($this->key === 'home' ? '/home' : $this->sitePath());
    }

    /** True when the saved draft differs from the live version (ignoring empty lists, hidden = false, verified = true). */
    public function hasUnpublishedChanges(): bool
    {
        return $this->publishedRevision === null || self::normalize($this->publishedRevision->sections) != self::normalize($this->draft_sections ?? []);
    }

    private static function normalize(array $value): array
    {
        $out = [];
        foreach ($value as $k => $v) {
            if ($v === [] || $v === null || ($k === 'hidden' && $v === false) || ($k === 'verified' && $v === true)) {
                continue;
            }
            $out[$k] = is_array($v) ? self::normalize($v) : $v;
        }

        return $out;
    }
}
