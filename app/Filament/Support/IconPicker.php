<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;

/** Icon select with previews; the list is generated with the site's icon bundle (scripts/build-cms-icons.mjs). */
final class IconPicker
{
    /** @var array<string, string>|null name => svg */
    private static ?array $icons = null;

    /** @return array<string, string> */
    public static function icons(): array
    {
        return self::$icons ??= json_decode(file_get_contents(resource_path('icons/cms-icons.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function make(string $name = 'icon'): Select
    {
        return Select::make($name)->label(__('Icon'))->native(false)->searchable()->allowHtml()
            ->options(fn () => collect(self::icons())->mapWithKeys(fn (string $svg, string $icon) => [
                // The SVG comes from the generated file, never from user input.
                $icon => '<span style="display:flex;align-items:center;gap:.6rem;color:#b8862f">'.$svg.'<span style="color:inherit;opacity:.85">'.e($icon).'</span></span>',
            ])->all())
            ->in(array_keys(self::icons()));
    }
}
