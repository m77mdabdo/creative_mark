<?php

namespace App\Consultation;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Mail\ConsultationReceived;
use App\Mail\ConsultationReplyMail;
use App\Mail\NewConsultationBooked;
use App\Models\ConsultationReply;
use App\Models\ConsultationRequest;
use App\Models\NotificationRecipient;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends everything a new booking triggers:
 *  - one email per active recipient (separate messages, so recipients never see each other's addresses),
 *  - the client's confirmation in their language,
 *  - a bell notification in the dashboard for staff who manage bookings.
 * Each step is independent: a mail failure is recorded on the booking and never loses the booking itself.
 */
final class Notifier
{
    public function bookingCreated(ConsultationRequest $booking): void
    {
        $errors = [];

        $sent = 0;
        foreach (NotificationRecipient::query()->where('active', true)->get() as $recipient) {
            try {
                Mail::to($recipient->email, $recipient->name)->send(new NewConsultationBooked($booking));
                $recipient->forceFill(['last_notified_at' => now()])->save();
                $sent++;
            } catch (Throwable $e) {
                $errors[] = "team {$recipient->email}: ".$e->getMessage();
            }
        }

        try {
            Mail::to($booking->email, $booking->name)->send(new ConsultationReceived($booking));
            $booking->confirmed_at = now();
        } catch (Throwable $e) {
            $errors[] = 'client: '.$e->getMessage();
        }

        try {
            $this->notifyDashboard($booking);
        } catch (Throwable $e) {
            $errors[] = 'dashboard: '.$e->getMessage();
        }

        $booking->notified_at = $sent > 0 ? now() : $booking->notified_at;
        $booking->mail_error = $errors ? mb_strimwidth(implode(' | ', $errors), 0, 500) : null;
        $booking->save();

        if ($errors) {
            Log::warning('Consultation notifications incomplete', ['reference' => $booking->reference, 'errors' => $errors]);
        }
    }

    /** Sends a reply written in the dashboard and records the outcome on the reply. */
    public function sendReply(ConsultationReply $reply): bool
    {
        $reply->loadMissing(['request', 'author']);
        try {
            Mail::to($reply->request->email, $reply->request->name)->send(new ConsultationReplyMail($reply));
            $reply->forceFill(['sent_at' => now(), 'error' => null])->save();

            return true;
        } catch (Throwable $e) {
            $reply->forceFill(['error' => mb_strimwidth($e->getMessage(), 0, 500)])->save();
            Log::warning('Consultation reply failed', ['reply' => $reply->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function notifyDashboard(ConsultationRequest $booking): void
    {
        $staff = User::query()->where('is_active', true)->get()->filter(fn (User $u) => $u->can('leads.assign'));
        if ($staff->isEmpty()) {
            return;
        }

        Notification::make()
            ->title(__('New consultation booking').' '.$booking->reference)
            ->body($booking->name.' · '.BookingPresenter::when($booking, 'en'))
            ->icon('heroicon-o-calendar-days')
            ->iconColor('warning')
            ->actions([Action::make('open')->label(__('Open'))->url(ConsultationRequestResource::getUrl('view', ['record' => $booking]))])
            ->sendToDatabase($staff);
    }
}
