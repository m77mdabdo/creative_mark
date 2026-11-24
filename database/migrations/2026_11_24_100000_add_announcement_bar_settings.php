<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * The announcement bar above the menu (Settings › Announcement bar) starts with the messages in
 * database/seeders/data/announcement.json. Sites that already have announcement settings keep them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Setting::query()->where('group', 'announcement')->exists()) {
            return;
        }
        foreach (json_decode(file_get_contents(database_path('seeders/data/announcement.json')), true, flags: JSON_THROW_ON_ERROR) as $key => $value) {
            Setting::create(['group' => 'announcement', 'key' => $key, 'value' => $value]);
        }
    }

    public function down(): void
    {
        Setting::query()->where('group', 'announcement')->delete();
    }
};
