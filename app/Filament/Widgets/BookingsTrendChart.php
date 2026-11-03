<?php

namespace App\Filament\Widgets;

use App\Models\ConsultationRequest;
use Filament\Widgets\ChartWidget;

/** Bookings received per day over the last 30 days. */
class BookingsTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('viewAny', ConsultationRequest::class);
    }

    public function getHeading(): string
    {
        return __('Bookings in the last 30 days');
    }

    protected function getData(): array
    {
        $tz = config('consultation.timezone');
        $days = collect(range(29, 0))->map(fn ($d) => now($tz)->subDays($d));
        $counts = ConsultationRequest::query()->visibleTo(auth()->user())
            ->where('created_at', '>=', $days->first()->copy()->startOfDay())
            ->get(['created_at'])->countBy(fn ($b) => $b->created_at->timezone($tz)->toDateString());

        return [
            'datasets' => [[
                'label' => __('Bookings'),
                'data' => $days->map(fn ($d) => $counts[$d->toDateString()] ?? 0)->all(),
                'borderColor' => '#d4a24c', 'backgroundColor' => 'rgba(235, 189, 108, .25)', 'fill' => true, 'tension' => .35,
                'pointRadius' => 3, 'pointBackgroundColor' => '#d4a24c',
            ]],
            'labels' => $days->map(fn ($d) => $d->locale(app()->getLocale())->translatedFormat('j M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }
}
