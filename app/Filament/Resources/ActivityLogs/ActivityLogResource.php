<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Read-only activity log: nobody can add, change or delete a line from the dashboard. */
class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getModelLabel(): string
    {
        return __('Activity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Activity log');
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('audit.view');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->columns([
                TextColumn::make('created_at')->label(__('When'))->sortable()
                    ->formatStateUsing(fn ($state) => $state->timezone(config('consultation.timezone'))->locale(app()->getLocale())->translatedFormat('j M Y · H:i'))
                    ->description(fn (ActivityLog $r) => $r->created_at->locale(app()->getLocale())->diffForHumans()),
                TextColumn::make('user_name')->label(__('Who'))->placeholder('—')->searchable(),
                TextColumn::make('event')->label(__('What'))->badge()
                    ->formatStateUsing(fn (string $state) => ActivityLog::eventLabels()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'deleted', 'login_failed' => 'danger', 'published', 'created', 'invited' => 'success', 'exported', 'mfa_reset' => 'warning', 'login', 'logout' => 'gray', default => 'info',
                    }),
                TextColumn::make('subject_type')->label(__('Type'))->placeholder('—')->formatStateUsing(fn (?string $state) => ActivityLog::typeLabels()[$state] ?? $state),
                TextColumn::make('subject_label')->label(__('Item'))->placeholder('—')->searchable()->wrap(),
                TextColumn::make('fields')->label(__('Changed fields'))->listWithLineBreaks()->limitList(4)->expandableLimitedList()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')->label(__('What'))->options(fn () => ActivityLog::eventLabels()),
                SelectFilter::make('subject_type')->label(__('Type'))->options(fn () => ActivityLog::typeLabels()),
                SelectFilter::make('user_id')->label(__('Who'))->relationship('user', 'name')->searchable()->preload(),
                Filter::make('dates')->schema([
                    DatePicker::make('from')->label(__('From')),
                    DatePicker::make('until')->label(__('To')),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($x, $v) => $x->whereDate('created_at', '>=', $v))
                    ->when($data['until'] ?? null, fn ($x, $v) => $x->whereDate('created_at', '<=', $v))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivityLogs::route('/')];
    }
}
