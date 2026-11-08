<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/** An image of the site, known by `key`; its renditions are public/media/{folder}/{key}-{width}.webp (App\Content\MediaLibrary). */
class MediaAsset extends Model
{
    use HasTranslations;

    protected $fillable = ['key', 'folder', 'widths', 'credit', 'source', 'uploaded_by'];

    protected function casts(): array
    {
        return ['widths' => 'array'];
    }

    protected function translationModel(): string
    {
        return MediaAssetTranslation::class;
    }

    public function isUpload(): bool
    {
        return $this->folder === 'uploads';
    }
}
