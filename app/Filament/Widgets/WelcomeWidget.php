<?php

namespace App\Filament\Widgets;

use App\Enums\ConsultationStatus;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\NewsArticles\NewsArticleResource;
use App\Filament\Resources\NotificationRecipients\NotificationRecipientResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\ConsultationRequest;
use Filament\Widgets\Widget;

/** Greeting, today's headline numbers and quick actions; each action appears only to staff who may use it. */
class WelcomeWidget extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.welcome';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $tz = config('consultation.timezone');
        $hour = (int) now($tz)->format('G');
        $canBookings = $user?->can('viewAny', ConsultationRequest::class);
        $bookings = $canBookings ? ConsultationRequest::query()->visibleTo($user) : null;

        $actions = array_values(array_filter([
            $canBookings ? ['label' => __('Review bookings'), 'url' => ConsultationRequestResource::getUrl(), 'icon' => 'heroicon-o-calendar-days', 'tone' => 'amber'] : null,
            NewsArticleResource::canCreate() ? ['label' => __('Add a news story'), 'url' => NewsArticleResource::getUrl('create'), 'icon' => 'heroicon-o-newspaper', 'tone' => 'sky'] : null,
            CaseStudyResource::canCreate() ? ['label' => __('Add a case study'), 'url' => CaseStudyResource::getUrl('create'), 'icon' => 'heroicon-o-briefcase', 'tone' => 'violet'] : null,
            ServiceResource::canViewAny() ? ['label' => __('Manage services'), 'url' => ServiceResource::getUrl(), 'icon' => 'heroicon-o-squares-2x2', 'tone' => 'emerald'] : null,
            FaqResource::canViewAny() ? ['label' => __('Edit FAQs'), 'url' => FaqResource::getUrl(), 'icon' => 'heroicon-o-question-mark-circle', 'tone' => 'rose'] : null,
            NotificationRecipientResource::canViewAny() ? ['label' => __('Booking emails'), 'url' => NotificationRecipientResource::getUrl(), 'icon' => 'heroicon-o-bell-alert', 'tone' => 'orange'] : null,
        ]));

        return [
            'greeting' => __($hour < 12 ? 'Good morning, :name' : 'Good evening, :name', ['name' => strtok((string) $user?->name, ' ')]),
            'date' => now($tz)->locale(app()->getLocale())->translatedFormat('l j F Y'),
            'newCount' => $bookings ? (clone $bookings)->where('status', ConsultationStatus::New)->count() : null,
            'weekCount' => $bookings ? (clone $bookings)->holdingSlot()->whereBetween('preferred_date', [now($tz)->toDateString(), now($tz)->addDays(7)->toDateString()])->count() : null,
            'siteUrl' => url('/'.app()->getLocale()),
            'actions' => $actions,
        ];
    }
}
