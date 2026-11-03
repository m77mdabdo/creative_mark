<?php

namespace App\Filament\Resources\ConsultationRequests\Schemas;

use App\Consultation\BookingPresenter;
use App\Consultation\MailStrings;
use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\Region;
use App\Models\Service;
use App\Support\Locales;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConsultationRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(2)->schema([
                Section::make(__('Booking'))->columns(2)->schema([
                    TextEntry::make('reference')->label(__('Reference'))->copyable()->fontFamily('mono'),
                    TextEntry::make('status')->label(__('Status'))->badge()
                        ->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
                    TextEntry::make('preferred')->label(__('Preferred time'))
                        ->state(fn (ConsultationRequest $r) => BookingPresenter::when($r, app()->getLocale()))->columnSpanFull(),
                    TextEntry::make('meeting_mode')->label(__('Meeting'))->formatStateUsing(fn ($state) => MailStrings::get(app()->getLocale(), "mode_{$state}")),
                    TextEntry::make('stage')->label(__('Stage'))->formatStateUsing(fn ($state) => MailStrings::get(app()->getLocale(), "stage_{$state}"))->placeholder('—'),
                    TextEntry::make('service')->label(__('Service'))->placeholder('—')
                        ->formatStateUsing(fn ($state) => Service::query()->where('slug', $state)->withLocale(app()->getLocale())->first()?->t('title', app()->getLocale()) ?? $state),
                    TextEntry::make('region')->label(__('Region'))->placeholder('—')
                        ->formatStateUsing(fn ($state) => Region::query()->where('slug', $state)->withLocale(app()->getLocale())->first()?->t('name', app()->getLocale()) ?? $state),
                    TextEntry::make('message')->label(__('Message'))->placeholder('—')->columnSpanFull()->prose(),
                ]),
                Section::make(__('Replies sent'))->schema([
                    RepeatableEntry::make('replies')->hiddenLabel()->placeholder(__('No replies yet.'))->schema([
                        TextEntry::make('subject')->label(__('Subject'))->weight('bold'),
                        TextEntry::make('body')->label(__('Message'))->prose(),
                        TextEntry::make('author.name')->label(__('By'))->placeholder('—'),
                        TextEntry::make('sent_at')->label(__('Sent'))->dateTime()->placeholder(__('Not sent')),
                        TextEntry::make('error')->label(__('Error'))->color('danger')->visible(fn ($state) => filled($state)),
                    ])->columns(2),
                ]),
            ]),
            Grid::make(1)->columnSpan(1)->schema([
                Section::make(__('Client'))->schema([
                    TextEntry::make('name')->label(__('Name')),
                    TextEntry::make('email')->label(__('Email'))->extraAttributes(['dir' => 'ltr'])->copyable()->url(fn (ConsultationRequest $r) => 'mailto:'.$r->email),
                    TextEntry::make('phone')->label(__('Phone'))->extraAttributes(['dir' => 'ltr'])->copyable()->url(fn (ConsultationRequest $r) => 'tel:'.preg_replace('/[^0-9+]/', '', $r->phone)),
                    TextEntry::make('company')->label(__('Company'))->placeholder('—'),
                    TextEntry::make('country')->label(__('Based in'))->placeholder('—')
                        ->formatStateUsing(fn ($state) => $state === 'xx' ? __('Other') : (Country::query()->where('code', $state)->withLocale(app()->getLocale())->first()?->t('name', app()->getLocale()) ?? strtoupper($state))),
                    TextEntry::make('locale')->label(__('Language'))->formatStateUsing(fn ($state) => Locales::ALL[$state]['native'] ?? $state),
                ]),
                Section::make(__('Team'))->schema([
                    TextEntry::make('assignee.name')->label(__('Assigned to'))->placeholder(__('Unassigned')),
                    TextEntry::make('internal_notes')->label(__('Internal notes'))->placeholder('—'),
                ]),
                Section::make(__('Delivery'))->schema([
                    TextEntry::make('created_at')->label(__('Received'))->dateTime(),
                    TextEntry::make('notified_at')->label(__('Team emailed'))->dateTime()->placeholder(__('Not sent')),
                    TextEntry::make('confirmed_at')->label(__('Client confirmation'))->dateTime()->placeholder(__('Not sent')),
                    TextEntry::make('mail_error')->label(__('Email errors'))->color('danger')->visible(fn ($state) => filled($state)),
                    TextEntry::make('consent_at')->label(__('Consent given'))->dateTime(),
                ]),
            ]),
        ]);
    }
}
