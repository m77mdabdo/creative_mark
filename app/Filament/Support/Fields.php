<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;

/** Field definitions shared by every catalogue resource, so behaviour stays identical across modules. */
final class Fields
{
    /** Draft/published. Only content.publish may make a record live (RULE-05); new records start as drafts. */
    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('Status'))
            ->options(['draft' => __('Draft'), 'published' => __('Published')])
            ->default('draft')
            ->required()
            ->disabled(fn () => ! auth()->user()?->can('content.publish'))
            ->dehydrated(fn () => (bool) auth()->user()?->can('content.publish'))
            ->helperText(fn () => auth()->user()?->can('content.publish') ? null : __('A Content Manager publishes your draft.'));
    }

    /** An image from the media library, with thumbnails, a preview and upload (App\Content\MediaLibrary). */
    public static function media(string $name = 'media'): Select
    {
        return SectionForm::mediaSelect($name, __('Image'));
    }

    /** An icon from the site's icon set, with previews. */
    public static function icon(): Select
    {
        return IconPicker::make('icon');
    }
}
