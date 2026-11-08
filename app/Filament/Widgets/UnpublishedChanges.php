<?php

namespace App\Filament\Widgets;

use App\Drafts\Draft;
use App\Drafts\PreviewMode;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Everything saved but not yet on the website, with who changed it, so nothing is forgotten before publishing. */
class UnpublishedChanges extends TableWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return PreviewMode::allowed(auth()->user()) && Draft::query()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Unpublished changes'))
            ->description(__('Saved but not on the website yet. Open one to preview and publish it.'))
            ->query(fn () => Draft::query()->with('author')->latest('updated_at'))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Draft $d) => $d->edit_url)
            ->headerActions([
                Action::make('previewAll')->label(__('Preview the site'))->icon('heroicon-o-eye')->color('gray')
                    ->url(fn () => route('preview.start', ['path' => '/', 'locale' => app()->getLocale()]), shouldOpenInNewTab: true),
            ])
            ->columns([
                TextColumn::make('label')->label(__('What'))->wrap(),
                TextColumn::make('author.name')->label(__('By'))->placeholder('—'),
                TextColumn::make('updated_at')->label(__('Last saved'))->since(),
            ]);
    }
}
