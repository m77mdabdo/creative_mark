<?php

namespace App\Filament\Widgets;

use App\Enums\ConsultationStatus;
use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/** Booking figures for staff who handle consultations (agents see only their own). */
class BookingStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('viewAny', ConsultationRequest::class);
    }

    /** Two figures per row on phones, four on large screens. */
    protected function getColumns(): int|array
    {
        return ['default' => 2, 'lg' => 4];
    }

    protected function getStats(): array
    {
        $tz = config('consultation.timezone');
        $q = fn (): Builder => ConsultationRequest::query()->visibleTo(auth()->user());
        $daily = fn (int $days, ?callable $scope = null) => collect(range($days - 1, 0))
            ->map(fn ($d) => ($scope ? $scope($q()) : $q())->whereDate('created_at', now($tz)->subDays($d)->toDateString())->count())->all();

        $thisMonth = $q()->where('created_at', '>=', now($tz)->startOfMonth())->count();
        $lastMonth = $q()->whereBetween('created_at', [now($tz)->subMonthNoOverflow()->startOfMonth(), now($tz)->subMonthNoOverflow()->endOfMonth()])->count();
        $change = $lastMonth ? (int) round(($thisMonth - $lastMonth) / $lastMonth * 100) : null;

        $closed = $q()->whereIn('status', [ConsultationStatus::Completed, ConsultationStatus::Cancelled])->count();
        $completed = $q()->where('status', ConsultationStatus::Completed)->count();

        return [
            Stat::make(__('New bookings'), $q()->where('status', ConsultationStatus::New)->count())
                ->description(__('Waiting for the team'))->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->chart($daily(14, fn ($b) => $b))->color('warning')
                ->url(ConsultationRequestResource::getUrl('index', ['filters' => ['status' => ['value' => 'new']]])),
            Stat::make(__('This month'), $thisMonth)
                ->description($change === null ? __('Same as last month') : __(':change% vs last month', ['change' => ($change > 0 ? '+' : '').$change]))
                ->descriptionIcon($change !== null && $change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->chart($daily(30))->color($change !== null && $change < 0 ? 'danger' : 'success'),
            Stat::make(__('Upcoming meetings'), $q()->holdingSlot()->whereBetween('preferred_date', [now($tz)->toDateString(), now($tz)->addDays(7)->toDateString()])->count())
                ->description(__('Next 7 days'))->descriptionIcon('heroicon-m-calendar-days')->color('info'),
            Stat::make(__('Completion rate'), $closed ? round($completed / $closed * 100).'%' : '—')
                ->description(__('Completed of all closed bookings'))->descriptionIcon('heroicon-m-check-badge')->color('primary'),
        ];
    }
}
