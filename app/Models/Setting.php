<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Key-value site settings grouped by area (contact, social, offices, flags). */
class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'is_sensitive'];

    protected function casts(): array
    {
        return ['value' => 'array', 'is_sensitive' => 'boolean'];
    }

    /** @return array<string, mixed> key => value for one group, never sensitive entries. */
    public static function group(string $group): array
    {
        return static::query()->where('group', $group)->where('is_sensitive', false)->get()
            ->mapWithKeys(fn (self $s) => [$s->key => $s->value])->all();
    }
}
