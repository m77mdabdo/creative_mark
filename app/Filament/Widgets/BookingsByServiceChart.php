<?php

namespace App\Filament\Widgets;

use App\Models\ConsultationRequest;
use App\Models\Service;
use Filament\Widgets\ChartWidget;

/** Which business solutions clients ask about. */
class BookingsByServiceChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('viewAny', ConsultationRequest::class);
    }

    public function getHeading(): string
    {
        return __('Bookings by service');
    }

    protected function getData(): array
    {
        $locale = app()->getLocale();
        $counts = ConsultationRequest::query()->visibleTo(auth()->user())->toBase()
            ->selectRaw('service, count(*) as c')->groupBy('service')->orderByDesc('c')->pluck('c', 'service');
        $titles = Service::query()->withLocale($locale)->get()->mapWithKeys(fn (Service $s) => [$s->slug => $s->t('title', $locale)]);
        $palette = ['#ebbd6c', '#38bdf8', '#34d399', '#a78bfa', '#fb7185', '#fb923c', '#22d3ee', '#facc15', '#4ade80', '#f472b6', '#60a5fa', '#c084fc'];

        return [
            'datasets' => [['label' => __('Bookings'), 'data' => $counts->values()->all(), 'backgroundColor' => array_slice(array_merge($palette, $palette), 0, $counts->count()), 'borderRadius' => 8]],
            'labels' => $counts->keys()->map(fn ($slug) => $slug ? ($titles[$slug] ?? $slug) : __('Not sure yet'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }
}
