<?php

namespace App\Filament\Pages;

use App\Audit\Audit;
use App\Content\MediaLibrary;
use App\Filament\Support\SectionForm;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;
use RuntimeException;

/**
 * Every image on the site: search it, see where each one is used, upload new ones (re-encoded by MediaLibrary) and
 * edit descriptions per language. Only uploaded images that nothing uses can be deleted.
 */
class MediaLibraryPage extends Page
{
    protected static ?string $slug = 'media-library';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.media-library';

    public string $search = '';

    public string $show = 'all';

    public int $limit = 48;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('media.upload');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Content');
    }

    public static function getNavigationLabel(): string
    {
        return __('Media library');
    }

    public function getTitle(): string
    {
        return __('Media library');
    }

    public function updatedSearch(): void
    {
        $this->limit = 48;
    }

    public function setShow(string $show): void
    {
        $this->show = in_array($show, ['all', 'uploads', 'unused'], true) ? $show : 'all';
        $this->limit = 48;
    }

    public function loadMore(): void
    {
        $this->limit += 48;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        MediaLibrary::flush();
        $usage = MediaLibrary::usage();
        $query = mb_strtolower(trim($this->search));
        $canDelete = (bool) auth()->user()?->can('media.delete');

        $items = collect(MediaLibrary::all())
            ->sortByDesc(fn ($m) => $m['uploaded_at'] ?? '')
            ->filter(fn ($m, $key) => match ($this->show) {
                'uploads' => ($m['folder'] ?? null) === 'uploads',
                'unused' => empty($usage[$key]),
                default => true,
            })
            ->filter(fn ($m, $key) => $query === '' || str_contains(mb_strtolower($key.' '.implode(' ', $m['alt'] ?? [])), $query));

        return [
            'total' => $items->count(),
            'items' => $items->take($this->limit)->map(fn ($m, $key) => [
                'key' => $key, 'url' => MediaLibrary::url($key), 'alt' => MediaLibrary::alt($key), 'uploaded' => ($m['folder'] ?? null) === 'uploads',
                'places' => $usage[$key] ?? [], 'width' => max($m['widths'] ?? [800]), 'canDelete' => $canDelete && MediaLibrary::canDelete($key, $usage),
            ])->all(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')->label(__('Upload an image'))->icon('heroicon-o-arrow-up-tray')
                ->modalHeading(__('Upload a new image'))
                ->schema([
                    FileUpload::make('file')->label(__('Image'))->image()->required()->maxSize(12 * 1024)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('local')->directory('media-uploads')->visibility('private')
                        ->helperText(__('JPG, PNG or WebP, up to 12 MB. Large images look best: 2400 pixels wide or more.')),
                    Fieldset::make(__('Description for screen readers and Google'))->schema([
                        TextInput::make('alt_en')->label('English')->required()->maxLength(160),
                        TextInput::make('alt_ar')->label('العربية')->maxLength(160),
                    ]),
                ])
                ->action(function (array $data) {
                    $path = storage_path('app/private/'.$data['file']);
                    try {
                        $key = MediaLibrary::store($path, $data['alt_en'], $data['alt_ar'] ?? null);
                        Audit::record('created', null, $data['alt_en'], [$key], type: 'Media');
                        Notification::make()->success()->title(__('Image uploaded. You can now choose it in any editor.'))->send();
                    } catch (RuntimeException $e) {
                        Notification::make()->danger()->title(__('This file could not be read as an image.'))->send();
                    } finally {
                        @unlink($path);
                    }
                }),
        ];
    }

    public function editAction(): Action
    {
        return Action::make('edit')->label(__('Edit'))->modalHeading(__('Image description'))->modalSubmitActionLabel(__('Save'))
            ->fillForm(fn (array $arguments) => ['alt' => MediaLibrary::all()[$arguments['key']]['alt'] ?? []])
            ->schema([SectionForm::localeTabs(fn (string $l, bool $en) => [TextInput::make("alt.{$l}")->label(__('Description'))->required($en)->maxLength(160)])])
            ->action(function (array $data, array $arguments) {
                MediaLibrary::updateAlt($arguments['key'], $data['alt'] ?? []);
                Audit::record('updated', null, $data['alt']['en'] ?? $arguments['key'], [$arguments['key']], type: 'Media');
                Notification::make()->success()->title(__('Saved'))->send();
            });
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')->label(__('Delete'))->color('danger')->requiresConfirmation()
            ->modalHeading(__('Delete this image?'))->modalDescription(__('Nothing on the site uses it. This cannot be undone.'))
            ->action(function (array $arguments) {
                abort_unless(auth()->user()?->can('media.delete'), 403);
                $key = $arguments['key'];
                if (! MediaLibrary::canDelete($key)) {
                    Notification::make()->danger()->title(__('This image is in use, or comes with the site, so it cannot be deleted.'))->send();

                    return;
                }
                $alt = MediaLibrary::alt($key);
                MediaLibrary::delete($key);
                Audit::record('deleted', null, $alt, [$key], type: 'Media');
                Notification::make()->success()->title(__('Image deleted'))->send();
            });
    }
}
