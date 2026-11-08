<?php

use App\Enums\ReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Images get their own table instead of one JSON setting (media.placeholders): each upload is its own row, so two
 * uploads at the same time can no longer overwrite each other, and the description (alt text) is translated like
 * every other record, one row per language (ADR-0003). Existing images are moved over unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $t) {
            $t->id();
            $t->string('key', 100)->unique(); // referenced by content as `media`
            $t->string('folder', 32)->default('placeholders'); // public/media/{folder}
            $t->json('widths'); // renditions on disk: {key}-{width}.webp
            $t->string('credit', 120)->nullable();
            $t->string('source', 500)->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['folder', 'created_at']);
        });
        Schema::create('media_asset_translations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $t->string('locale', 8);
            $t->string('alt', 255);
            $t->string('review_status', 16)->default('missing');
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['media_asset_id', 'locale']);
        });

        $setting = DB::table('settings')->where('group', 'media')->where('key', 'placeholders')->first();
        foreach (json_decode($setting?->value ?? '[]', true) ?: [] as $key => $item) {
            $uploaded = $item['uploaded_at'] ?? null;
            $id = DB::table('media_assets')->insertGetId([
                'key' => $key, 'folder' => $item['folder'] ?? 'placeholders', 'widths' => json_encode($item['widths'] ?? [800, 1600]),
                'credit' => $item['credit'] ?? null, 'source' => $item['source'] ?? null,
                'created_at' => $uploaded ? date('Y-m-d H:i:s', strtotime($uploaded)) : now(), 'updated_at' => now(),
            ]);
            foreach ($item['alt'] ?? [] as $locale => $alt) {
                if (filled($alt)) {
                    DB::table('media_asset_translations')->insert([
                        'media_asset_id' => $id, 'locale' => $locale, 'alt' => mb_substr(trim($alt), 0, 255),
                        // English and Arabic were written by people; the other languages came from machine translation.
                        'review_status' => in_array($locale, ['en', 'ar'], true) ? ReviewStatus::Approved->value : ReviewStatus::Machine->value,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
        DB::table('settings')->where('group', 'media')->where('key', 'placeholders')->delete();
    }

    public function down(): void
    {
        $registry = [];
        foreach (DB::table('media_assets')->get() as $asset) {
            $registry[$asset->key] = array_filter([
                'alt' => DB::table('media_asset_translations')->where('media_asset_id', $asset->id)->pluck('alt', 'locale')->all(),
                'folder' => $asset->folder, 'widths' => json_decode($asset->widths, true), 'credit' => $asset->credit, 'source' => $asset->source,
                'uploaded_at' => $asset->folder === 'uploads' ? date(DATE_ATOM, strtotime($asset->created_at)) : null,
            ], fn ($v) => $v !== null);
        }
        DB::table('settings')->updateOrInsert(['group' => 'media', 'key' => 'placeholders'], ['value' => json_encode($registry), 'is_sensitive' => false, 'updated_at' => now()]);
        Schema::dropIfExists('media_asset_translations');
        Schema::dropIfExists('media_assets');
    }
};
