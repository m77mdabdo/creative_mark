<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\PublishedByStatus;
use App\Models\Contracts\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model implements Publishable
{
    use HasTranslations, PublishedByStatus;

    protected $fillable = ['slug', 'icon', 'media', 'sort_order', 'status', 'needs_review', 'group', 'related'];

    protected function casts(): array
    {
        return ['needs_review' => 'boolean', 'related' => 'array'];
    }

    protected function translationModel(): string
    {
        return ServiceTranslation::class;
    }

    public function faqs(): BelongsToMany
    {
        return $this->belongsToMany(Faq::class);
    }

    public function sitePath(): string
    {
        return '/services/'.$this->slug;
    }
}
