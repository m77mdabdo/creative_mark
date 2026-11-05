<?php

namespace App\Filament\Resources\NewsArticles\Tables;

use App\Filament\Support\Columns;
use App\Models\NewsArticle;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->defaultSort('published_on', 'desc')
            ->columns([
                TextColumn::make('published_on')->label(__('Date'))->date()->sortable(),
                Columns::translated('title', __('Headline')),
                TextColumn::make('category')->label(__('Category'))->badge()->formatStateUsing(fn (string $state) => NewsArticle::categoryLabels()[$state] ?? $state),
                TextColumn::make('source_name')->label(__('Source')),
                IconColumn::make('featured')->label(__('Lead'))->boolean(),
                Columns::localeStatus(),
                TextColumn::make('schedule')->label(__('Status'))->badge()
                    ->state(fn (NewsArticle $r) => match (true) {
                        $r->isScheduled() => __('Scheduled to publish').' · '.$r->publish_at->timezone(config('consultation.timezone'))->locale(app()->getLocale())->translatedFormat('j M, H:i'),
                        $r->status === 'published' => __('Published'),
                        default => __('Draft'),
                    })
                    ->color(fn (NewsArticle $r) => $r->isScheduled() ? 'info' : ($r->status === 'published' ? 'success' : 'gray')),
            ])
            ->filters([
                SelectFilter::make('category')->label(__('Category'))->options(fn () => NewsArticle::categoryLabels()),
                SelectFilter::make('schedule')->label(__('Status'))
                    ->options(fn () => ['draft' => __('Draft'), 'scheduled' => __('Scheduled to publish'), 'live' => __('Published')])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'draft' => $query->where('status', 'draft'),
                        'scheduled' => $query->where('status', 'published')->where('publish_at', '>', now()),
                        'live' => $query->where('status', 'published')->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now())),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('schedule')->label(__('Schedule'))->icon('heroicon-o-clock')->color('info')
                    ->visible(fn () => (bool) auth()->user()?->can('content.publish'))
                    ->modalHeading(__('Schedule this story'))->modalSubmitActionLabel(__('Schedule'))
                    ->fillForm(fn (NewsArticle $r) => ['publish_at' => $r->publish_at])
                    ->schema([DateTimePicker::make('publish_at')->label(__('Publish on'))->seconds(false)->native(false)->required()
                        ->timezone(config('consultation.timezone'))->minDate(now())->helperText(__('Riyadh time.'))])
                    ->action(function (array $data, NewsArticle $r) {
                        $r->update(['status' => 'published', 'publish_at' => $data['publish_at']]);
                        Notification::make()->success()->title(__('Scheduled. It goes live by itself at that time.'))->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
