<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stats', function (Blueprint $t) {
            $t->string('icon', 64)->nullable()->after('value'); // name from the curated icon set (resources/js/components/Icon.tsx)
        });
    }

    public function down(): void
    {
        Schema::table('stats', fn (Blueprint $t) => $t->dropColumn('icon'));
    }
};
