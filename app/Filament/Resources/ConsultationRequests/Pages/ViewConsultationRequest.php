<?php

namespace App\Filament\Resources\ConsultationRequests\Pages;

use App\Consultation\MailStrings;
use App\Consultation\Notifier;
use App\Enums\ConsultationStatus;
use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewConsultationRequest extends ViewRecord
{
    protected static string $resource = ConsultationRequestResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['replies.author', 'assignee']);
    }

    protected function getHeaderActions(): array
    {
        /** @var ConsultationRequest $booking */
        $booking = $this->getRecord();

        return [
            Action::make('reply')
                ->label(__('Reply by email'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => auth()->user()?->can('reply', $booking))
                ->modalHeading(__('Reply to :name', ['name' => $booking->name]))
                ->modalDescription(__('Sent from the company mailbox to :email, in a Creative Mark email.', ['email' => $booking->email]))
                ->fillForm(fn () => ['subject' => MailStrings::get($booking->locale, 'your_booking').' · '.$booking->reference])
                ->schema([
                    TextInput::make('subject')->label(__('Subject'))->required()->maxLength(200),
                    Textarea::make('body')->label(__('Message'))->required()->rows(10)->maxLength(5000)
                        ->helperText(__('Plain text. The greeting and your name are added automatically.')),
                ])
                ->action(function (array $data) use ($booking) {
                    $reply = $booking->replies()->create([...$data, 'user_id' => auth()->id()])->setRelation('request', $booking);
                    if (app(Notifier::class)->sendReply($reply)) {
                        if ($booking->status === ConsultationStatus::New) {
                            $booking->update(['status' => ConsultationStatus::Contacted]);
                        }
                        Notification::make()->success()->title(__('Reply sent to :email', ['email' => $booking->email]))->send();
                    } else {
                        Notification::make()->danger()->title(__('The email could not be sent. It was saved; try again later.'))->persistent()->send();
                    }
                    $this->record = $this->resolveRecord($booking->getKey());
                }),
            Action::make('resend')
                ->label(__('Resend notifications'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => auth()->user()?->can('leads.assign') && ($booking->mail_error || ! $booking->notified_at))
                ->requiresConfirmation()
                ->action(function () use ($booking) {
                    app(Notifier::class)->bookingCreated($booking);
                    $booking->refresh();
                    $booking->mail_error
                        ? Notification::make()->danger()->title(__('Some emails failed: :error', ['error' => $booking->mail_error]))->send()
                        : Notification::make()->success()->title(__('Notifications sent'))->send();
                }),
            EditAction::make()->label(__('Update status')),
        ];
    }
}
