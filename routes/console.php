<?php

use App\Models\ActivityLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cm:announce-stories')->everyMinute()->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [ActivityLog::class]])->daily();
