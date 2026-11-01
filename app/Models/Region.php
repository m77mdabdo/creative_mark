<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Region extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'media', 'industries', 'sort_order', 'status', 'source'];

    protected function casts(): array
    {
        return ['industries' => 'array'];
    }

    protected function translationModel(): string
    {
        return RegionTranslation::class;
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }
}
