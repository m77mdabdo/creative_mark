<?php

namespace App\Drafts\Targets;

use App\Drafts\DraftTarget;
use App\Models\Setting;

/**
 * Settings screens: payload {writes: [{group, key, value, owns?}]}. With `owns`, the value is merged into the stored
 * map and only those keys are replaced, so two screens that share a setting (the interface wording) never overwrite
 * each other's work, whichever is published first.
 */
final class SettingsTarget implements DraftTarget
{
    public function apply(array $payload): void
    {
        foreach ($payload['writes'] ?? [] as $write) {
            $value = $write['value'];
            if (isset($write['owns'])) {
                $current = Setting::group($write['group'])[$write['key']] ?? [];
                $value = array_replace(array_diff_key(is_array($current) ? $current : [], array_flip($write['owns'])), (array) $value);
            }
            Setting::updateOrCreate(['group' => $write['group'], 'key' => $write['key']], ['value' => $value]);
        }
    }
}
