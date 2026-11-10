<?php

namespace App\Audit;

use App\Drafts\Draft;
use App\Models\ActivityLog;
use App\Models\MediaAsset;
use App\Models\MediaAssetTranslation;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records staff activity. Model changes arrive through wildcard Eloquent events (AppServiceProvider); anything else
 * (sign-in, export) calls record() directly. Only staff actions are logged: a visitor's booking or a seeder is not.
 * Only field names are kept, never values. A logging problem must never stop the work it describes.
 */
final class Audit
{
    /** Not worth a line, or recorded in clearer words by the screen that changed them (the media library). */
    private const IGNORED = [ActivityLog::class, PageRevision::class, MediaAsset::class, MediaAssetTranslation::class, Draft::class];

    /** Housekeeping columns that do not count as a change. */
    private const QUIET_FIELDS = ['updated_at', 'created_at', 'lock_version', 'remember_token', 'last_login_at', 'live_notified_at', 'notified_at', 'confirmed_at', 'mail_error', 'last_notified_at', 'reviewed_at'];

    private const SECRET_FIELDS = ['password'];

    public static function modelEvent(string $event, Model $model): void
    {
        try {
            if (! Auth::check() || in_array($model::class, self::IGNORED, true)) {
                return;
            }
            // A translation row counts as a change to the record it translates.
            if (Str::endsWith(class_basename($model), 'Translation')) {
                self::translationEvent($model);

                return;
            }
            $fields = $event === 'updated' ? array_keys(array_diff_key($model->getChanges(), array_flip(self::QUIET_FIELDS))) : [];
            if ($event === 'updated' && $fields === []) {
                return;
            }
            if ($model instanceof Page && in_array('published_revision_id', $fields, true)) {
                $event = 'published';
            }
            $fields = array_map(fn ($f) => in_array($f, self::SECRET_FIELDS, true) ? "{$f} (changed)" : $f, $fields);
            self::record($event, $model, null, $fields);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** @param list<string> $fields */
    public static function record(string $event, ?Model $subject = null, ?string $label = null, array $fields = [], ?User $user = null, ?string $type = null): void
    {
        try {
            $user ??= Auth::user();
            $type ??= $subject ? class_basename($subject) : null;
            $id = $subject?->getKey();
            // One save can touch a record and six translations: one line is enough.
            $duplicate = $id && ActivityLog::query()->where('user_id', $user?->id)->where('event', $event)
                ->where('subject_type', $type)->where('subject_id', $id)->where('created_at', '>=', now()->subSeconds(5))->first();
            if ($duplicate) {
                if ($fields) {
                    $duplicate->update(['fields' => array_values(array_unique([...($duplicate->fields ?? []), ...$fields]))]);
                }

                return;
            }
            ActivityLog::create([
                'user_id' => $user?->id, 'user_name' => $user?->name, 'event' => $event, 'subject_type' => $type, 'subject_id' => $id,
                'subject_label' => Str::limit($label ?? ($subject ? self::labelOf($subject) : ''), 190, '…'),
                'fields' => $fields ?: null,
                'ip_hash' => ($ip = request()?->ip()) ? hash_hmac('sha256', $ip, (string) config('app.key')) : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function translationEvent(Model $translation): void
    {
        $attributes = $translation->getAttributes();
        $owner = substr($translation::class, 0, -strlen('Translation'));
        $foreign = Str::snake(class_basename($owner)).'_id';
        if (! class_exists($owner) || empty($attributes[$foreign])) {
            return;
        }
        $changed = array_keys(array_diff_key($translation->getChanges(), array_flip(self::QUIET_FIELDS)));
        if ($translation->wasChanged() && $changed === []) {
            return;
        }
        $parent = $owner::query()->find($attributes[$foreign]);
        if ($parent) {
            $locale = $attributes['locale'] ?? '';
            self::record('updated', $parent, null, array_map(fn ($f) => "{$f} [{$locale}]", $changed));
        }
    }

    /** A readable name for the record, without triggering lazy loads. */
    private static function labelOf(Model $model): string
    {
        $attributes = $model->getAttributes();
        if ($model instanceof User) {
            return (string) ($attributes['name'] ?? '');
        }
        if (method_exists($model, 'translations')) {
            $row = $model->translations()->where('locale', 'en')->first()?->getAttributes() ?? [];
            foreach (['title', 'name', 'question', 'label', 'quote'] as $field) {
                if (filled($row[$field] ?? null)) {
                    return (string) $row[$field];
                }
            }
        }
        foreach (['reference', 'subject', 'name', 'title', 'slug', 'key', 'email'] as $field) {
            if (filled($attributes[$field] ?? null)) {
                return ($attributes['group'] ?? null) ? "{$attributes['group']}.{$attributes[$field]}" : (string) $attributes[$field];
            }
        }

        return '#'.$model->getKey();
    }
}
