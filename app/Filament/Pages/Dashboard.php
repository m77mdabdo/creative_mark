<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/** Home of the dashboard: one column on phones, three on large screens (widgets in app/Filament/Widgets). */
class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'lg' => 3];
    }
}
