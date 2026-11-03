<?php

namespace App\Filament\Widgets;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use Filament\Widgets\ChartWidget;

/** Share of bookings in each status. */
class BookingsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $maxHeight = '280px';

    private const COLORS = ['new' => '#f59e0b', 'contacted' => '#38bdf8', 'scheduled' => '#10b981', 'completed' => '#a3a3a3', 'cancelled' => '#f43f5e', 'spam' => '#7c3aed'];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('viewAny', ConsultationRequest::class);
    }

    public function getHeading(): string
    {
        return __('By status');
    }

    protected function getData(): array
    {
        $counts = ConsultationRequest::query()->visibleTo(auth()->user())->toBase()
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $statuses = collect(ConsultationStatus::cases())->filter(fn ($s) => ($counts[$s->value] ?? 0) > 0)->values();

        return [
            'datasets' => [['data' => $statuses->map(fn ($s) => $counts[$s->value])->all(), 'backgroundColor' => $statuses->map(fn ($s) => self::COLORS[$s->value])->all(), 'borderWidth' => 0]],
            'labels' => $statuses->map(fn ($s) => $s->label())->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return ['cutout' => '65%', 'plugins' => ['legend' => ['position' => 'bottom']], 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }
}
