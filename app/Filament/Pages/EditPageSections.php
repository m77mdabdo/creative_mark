<?php

namespace App\Filament\Pages;

use App\Content\PublishPage;
use App\Content\SectionSchema;
use App\Enums\ReviewStatus;
use App\Filament\Support\CatalogueInline;
use App\Filament\Support\SectionForm;
use App\Models\Page as PageModel;
use App\Support\Locales;
use App\Translation\ContentTranslator;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Visual editor for one CMS page: sections can be reordered, hidden, added, duplicated and removed, and every text,
 * image, button, card, slide and animation edited. Saving stores the draft; publishing makes it live as a new
 * revision (ADR-0004). The draft can be previewed in any language before it is published.
 */
abstract class EditPageSections extends Page
{
    /** Key of the page in the pages table, e.g. "home". */
    protected static string $pageKey;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public int $lockVersion = 0;

    protected string $view = 'filament-panels::pages.page';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('pages.edit');
    }

    /** Every page editor sits under "Pages" at the top of the menu. */
    public static function getNavigationGroup(): ?string
    {
        return __('Pages');
    }

    protected function record(): PageModel
    {
        return PageModel::query()->where('key', static::$pageKey)->with(['publishedRevision', 'translations'])->firstOrFail();
    }

    public function mount(): void
    {
        $page = $this->record();
        $this->lockVersion = (int) $page->lock_version;
        $this->form->fill([
            'seo' => $page->translations->mapWithKeys(fn ($t) => [$t->locale => ['title' => $t->title, 'seo_title' => $t->seo_title, 'seo_description' => $t->seo_description]])->all(),
            'sections' => array_map([$this, 'toForm'], $page->draft_sections ?? []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([self::seoSection(), SectionForm::make()])->statePath('data');
    }

    /** Page title and Google snippet per language, with a live preview of the search result. */
    private static function seoSection(): Section
    {
        return Section::make(__('Google and page title (SEO)'))->icon('heroicon-o-magnifying-glass')->collapsible()->collapsed()
            ->description(__('How the page appears in Google and in the browser tab. Saved with the draft and shown on the website straight away.'))
            ->schema([SectionForm::localeTabs(fn (string $l, bool $en) => [
                TextInput::make("seo.{$l}.title")->label(__('Page name'))->maxLength(120)->required($en),
                TextInput::make("seo.{$l}.seo_title")->label(__('Title in Google'))->maxLength(70)->live(onBlur: true)
                    ->helperText(fn ($state) => __(':count of 60 recommended characters', ['count' => mb_strlen((string) $state)])),
                Textarea::make("seo.{$l}.seo_description")->label(__('Description in Google'))->rows(2)->maxLength(170)->live(onBlur: true)
                    ->helperText(fn ($state) => __(':count of 155 recommended characters', ['count' => mb_strlen((string) $state)])),
                Placeholder::make("serp_{$l}")->label(__('Preview in Google'))->content(fn (Get $get) => new HtmlString(
                    '<div dir="'.Locales::ALL[$l]['dir'].'" style="max-width:600px;font-family:Arial,sans-serif;padding:.75rem 1rem;border:1px solid rgba(0,0,0,.08);border-radius:.75rem;background:#fff">'
                    .'<div style="font-size:12px;color:#4d5156">'.e(parse_url(config('app.url'), PHP_URL_HOST) ?: 'creativemark').' › '.$l.'</div>'
                    .'<div style="font-size:19px;line-height:1.3;color:#1a0dab;margin:.2rem 0">'.e(Str::limit((string) ($get("seo.{$l}.seo_title") ?: $get("seo.{$l}.title")), 65)).'</div>'
                    .'<div style="font-size:13px;line-height:1.5;color:#4d5156">'.e(Str::limit((string) $get("seo.{$l}.seo_description"), 160)).'</div></div>')),
            ])]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('save')]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        $page = $this->record();
        $live = $page->publishedRevision?->sections;
        $state = $live === null ? __('Not published yet') : (self::normalize($live) == self::normalize($page->draft_sections ?? [])
            ? __('The website shows this version') : __('There are saved changes that are not published yet'));

        return $state.($page->published_at ? ' · '.__('Last published :time', ['time' => $page->published_at->locale(app()->getLocale())->diffForHumans()]) : '');
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            Action::make('save')->label(__('Save draft'))->icon('heroicon-o-check')->color('gray')
                ->keyBindings(['mod+s'])->action(fn () => $this->save()),
            Action::make('preview')->label(__('Preview'))->icon('heroicon-o-eye')->color('gray')
                ->action(function () {
                    if ($this->save(notify: false)) {
                        $this->js('window.open('.json_encode(route('admin.pages.preview', ['key' => static::$pageKey, 'locale' => app()->getLocale()])).', "_blank")');
                    }
                }),
            Action::make('publish')->label(__('Publish'))->icon('heroicon-o-rocket-launch')->color('primary')
                ->visible(fn () => (bool) $user?->can('pages.publish'))
                ->requiresConfirmation()->modalHeading(__('Publish this page?'))->modalDescription(__('Visitors will see these changes straight away.'))
                ->action(function () {
                    if (! $this->save(notify: false)) {
                        return;
                    }
                    (new PublishPage)($this->record(), auth()->id(), 'Published from the page editor');
                    Notification::make()->success()->title(__('Published. The website now shows your changes.'))->send();
                }),
            Action::make('translate')->label(__('Translate missing languages'))->icon('heroicon-o-language')->color('gray')
                ->visible(fn () => (bool) $user?->can('translations.edit'))
                ->requiresConfirmation()->modalDescription(__('Empty languages are filled by machine translation and marked for review. Text a person wrote is never replaced.'))
                ->action(function () {
                    if (! $this->save(notify: false)) {
                        return;
                    }
                    try {
                        app(ContentTranslator::class)->translatePage($this->record());
                        $this->mount();
                        Notification::make()->success()->title(__('Missing languages filled. Review them before publishing.'))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('Translation service unavailable'))->body($e->getMessage())->send();
                    }
                }),
            CatalogueInline::statsAction()->visible(fn () => $user?->can('content.publish') && $this->hasSection('trackRecord')),
            CatalogueInline::testimonialsAction()->visible(fn () => $user?->can('content.publish') && $this->hasSection('testimonials')),
            Action::make('history')->label(__('Version history'))->icon('heroicon-o-clock')->color('gray')
                ->modalHeading(__('Version history'))->modalSubmitAction(false)->modalCancelActionLabel(__('Close'))->modalWidth('3xl')
                ->modalContent(fn () => view('filament.page-history', [
                    'revisions' => $this->record()->revisions()->with('author')->latest('id')->limit(40)->get(),
                    'liveId' => $this->record()->published_revision_id,
                ])),
            Action::make('discard')->label(__('Discard unpublished changes'))->icon('heroicon-o-arrow-uturn-left')->color('danger')->link()
                ->visible(fn () => $this->record()->publishedRevision !== null)
                ->requiresConfirmation()->modalDescription(__('The draft goes back to the version visitors see now.'))
                ->action(function () {
                    $page = $this->record();
                    $page->forceFill(['draft_sections' => $page->publishedRevision->sections, 'lock_version' => $page->lock_version + 1])->save();
                    $this->mount();
                    Notification::make()->success()->title(__('Changes discarded'))->send();
                }),
        ];
    }

    private function hasSection(string $type): bool
    {
        return collect($this->data['sections'] ?? [])->contains(fn ($s) => ($s['type'] ?? null) === $type);
    }

    /** Validates and stores the draft. Returns false (with a message) if it cannot be saved. */
    public function save(bool $notify = true): bool
    {
        $state = $this->form->getState();
        $sections = array_values(array_map([$this, 'fromForm'], $state['sections'] ?? []));
        try {
            SectionSchema::validate($sections);
        } catch (ValidationException $e) {
            $lines = collect($e->errors())->map(function ($messages, $key) use ($sections) {
                $i = (int) Str::afterLast($key, '.');

                return SectionForm::typeLabel($sections[$i]['type'] ?? null).' ('.($i + 1).'): '.implode(' ', $messages);
            })->take(5)->implode("\n");
            Notification::make()->danger()->title(__('Some sections need attention'))->body($lines)->persistent()->send();

            return false;
        }

        $page = $this->record();
        if ((int) $page->lock_version !== $this->lockVersion) {
            Notification::make()->danger()->title(__('Someone else saved this page while you were editing.'))
                ->body(__('Reload the page to see their changes, then make yours again.'))->persistent()->send();

            return false;
        }
        $page->forceFill(['draft_sections' => $sections, 'lock_version' => $page->lock_version + 1])->save();
        $this->lockVersion = $page->lock_version;
        $page->revisions()->create(['sections' => $sections, 'kind' => 'draft', 'note' => 'Saved in the page editor', 'created_by' => auth()->id()]);
        $this->saveSeo($page, $state['seo'] ?? []);

        if ($notify) {
            Notification::make()->success()->title(__('Draft saved. Publish to show it on the website.'))->send();
        }

        return true;
    }

    /** Page name and Google texts per language. A changed language goes back to review unless the editor may approve it. */
    private function saveSeo(PageModel $page, array $seo): void
    {
        $canApprove = (bool) auth()->user()?->can('translations.approve');
        foreach (Locales::codes() as $locale) {
            $values = array_map(fn ($v) => filled($v) ? trim((string) $v) : null, array_intersect_key($seo[$locale] ?? [], array_flip(['title', 'seo_title', 'seo_description'])));
            $translation = $page->translations->firstWhere('locale', $locale);
            if (! $translation && ! array_filter($values)) {
                continue;
            }
            $translation ??= $page->translations()->make(['locale' => $locale, 'slug' => $page->key === 'home' ? null : $page->key]);
            $translation->fill($values);
            if ($translation->isDirty() && $locale !== Locales::FALLBACK && ! $canApprove) {
                $translation->review_status = ReviewStatus::Review;
            }
            $translation->review_status ??= ReviewStatus::Review;
            if (blank($translation->title)) {
                $translation->title = $page->t('title', Locales::FALLBACK) ?? $page->key;
            }
            $translation->save();
        }
    }

    /** Puts an earlier version back into the draft (it still has to be published). */
    public function restoreRevision(int $id): void
    {
        abort_unless((bool) auth()->user()?->can('pages.edit'), 403);
        $page = $this->record();
        $revision = $page->revisions()->findOrFail($id);
        $page->forceFill(['draft_sections' => $revision->sections, 'lock_version' => $page->lock_version + 1])->save();
        $page->revisions()->create(['sections' => $revision->sections, 'kind' => 'draft', 'note' => 'Restored version #'.$revision->id, 'created_by' => auth()->id()]);
        $this->unmountAction();
        $this->mount();
        Notification::make()->success()->title(__('Version restored to the draft. Preview it, then publish.'))->send();
    }

    /** Ignores differences that do not change the page (empty lists, hidden = false). */
    private static function normalize(array $value): array
    {
        $out = [];
        foreach ($value as $k => $v) {
            if ($v === [] || $v === null || ($k === 'hidden' && $v === false)) {
                continue;
            }
            $out[$k] = is_array($v) ? self::normalize($v) : $v;
        }

        return $out;
    }

    /** @param array<string, mixed> $s */
    protected function toForm(array $s): array
    {
        return [...$s, 'visible' => empty($s['hidden'])];
    }

    /** Form state back to a clean section: empty languages and empty fields removed, ids for new items. */
    protected function fromForm(array $s): array
    {
        $map = fn ($v) => is_array($v) ? array_filter($v, fn ($t) => is_string($t) && trim($t) !== '') : $v;
        $clean = function (array $item, array $textKeys) use ($map) {
            foreach ($textKeys as $k) {
                if (array_key_exists($k, $item)) {
                    $item[$k] = $map($item[$k]);
                    if ($item[$k] === [] || $item[$k] === null) {
                        unset($item[$k]);
                    }
                }
            }
            $item['id'] = filled($item['id'] ?? null) ? $item['id'] : Str::lower(Str::random(8));

            return array_filter($item, fn ($v) => $v !== null && $v !== '');
        };

        $hidden = ! ($s['visible'] ?? true);
        unset($s['visible']);
        $s = $clean($s, ['eyebrow', 'title', 'highlight', 'subtitle', 'body', 'points', 'note']);
        if (empty($s['id']) || ! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $s['id'])) {
            $s['id'] = ($s['type'] ?? 'section').'-'.Str::lower(Str::random(5));
        }
        if ($hidden) {
            $s['hidden'] = true;
        } else {
            unset($s['hidden']);
        }
        $s['buttons'] = array_values(array_map(fn ($b) => ['label' => $map($b['label'] ?? []), 'href' => trim((string) ($b['href'] ?? '')), 'style' => $b['style'] ?? 'gold'], $s['buttons'] ?? []));
        $s['cards'] = array_values(array_map(fn ($c) => $clean([...$c, 'verified' => (bool) ($c['verified'] ?? true)], ['title', 'text']), $s['cards'] ?? []));
        $s['slides'] = array_values(array_map(fn ($c) => $clean($c, ['eyebrow', 'title', 'highlight', 'subtitle']), $s['slides'] ?? []));
        foreach (['limit'] as $k) {
            if (isset($s[$k])) {
                $s[$k] = (int) $s[$k];
            }
        }
        $s['style'] = array_filter([
            'theme' => $s['style']['theme'] ?? 'dark',
            'overlay' => isset($s['style']['overlay']) && $s['style']['overlay'] !== '' ? (int) $s['style']['overlay'] : 55,
            'padding' => $s['style']['padding'] ?? 'lg',
            'align' => $s['style']['align'] ?? 'start',
            'bgMedia' => $s['style']['bgMedia'] ?? null,
        ], fn ($v) => $v !== null);
        $s['motion'] = [
            'preset' => $s['motion']['preset'] ?? 'fade-up',
            'intensity' => (int) ($s['motion']['intensity'] ?? 100),
            'delay' => (int) ($s['motion']['delay'] ?? 0),
        ];

        return $s;
    }
}
