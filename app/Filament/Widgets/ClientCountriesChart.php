<?php

namespace App\Filament\Widgets;

use App\Models\ConsultationRequest;
use App\Models\Country;
use Filament\Widgets\ChartWidget;

/** Where clients who book are based (top countries, the rest grouped). */
class ClientCountriesChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('viewAny', ConsultationRequest::class);
    }

    public function getHeading(): string
    {
        return __('Where clients are based');
    }

    protected function getData(): array
    {
        $locale = app()->getLocale();
        $counts = ConsultationRequest::query()->visibleTo(auth()->user())->toBase()
            ->selectRaw('country, count(*) as c')->groupBy('country')->orderByDesc('c')->pluck('c', 'country');
        $names = Country::query()->withLocale($locale)->get()->mapWithKeys(fn (Country $c) => [$c->code => $c->t('name', $locale)]);
        $top = $counts->take(5);
        $rest = $counts->slice(5)->sum();
        $labels = $top->keys()->map(fn ($code) => $names[$code] ?? __('Other'))->all();
        $data = $top->values()->all();
        if ($rest) {
            $labels[] = __('Other');
            $data[] = $rest;
        }

        return [
            'datasets' => [['data' => $data, 'backgroundColor' => ['#ebbd6c', '#38bdf8', '#34d399', '#a78bfa', '#fb7185', '#d4d4d4'], 'borderWidth' => 0]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']], 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }
}
