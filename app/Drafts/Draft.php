<?php

namespace App\Drafts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An unpublished change. `target` names the DraftTarget that knows how to make it live; `payload` is what it needs.
 *
 * @property array<string, mixed> $payload
 */
class Draft extends Model
{
    protected $fillable = ['key', 'target', 'label', 'edit_url', 'payload', 'updated_by'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
