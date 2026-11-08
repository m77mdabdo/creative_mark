<?php

namespace App\Filament\Concerns;

use App\Drafts\Drafts;
use App\Drafts\Targets\SettingsTarget;
use App\Filament\Support\DraftActions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Settings screens that change what visitors see (wording, menu, contact details). Save keeps the changes as a
 * draft; Preview shows the site with them; Publish makes them live. A screen describes only how to read its
 * settings and which settings to write.
 */
trait KeepsSettingsAsDraft
{
    /** Fills the form from the stored settings. A saved draft is shown by running this with the draft applied. */
    abstract protected function fillFromSettings(): void;

    /**
     * The settings to write when published. `owns` merges the value into a shared map, replacing only those keys.
     *
     * @return list<array{group: string, key: string, value: mixed, owns?: list<string>}>
     */
    abstract protected function settingWrites(array $state): array;

    /** Where the change shows on the site, without the language prefix. */
    abstract protected function previewPath(): string;

    protected function canPublish(): bool
    {
        return (bool) auth()->user()?->can('content.publish');
    }

    public function mount(): void
    {
        app(Drafts::class)->peek($this->draftKey(), fn () => $this->fillFromSettings());
    }

    public function save(bool $notify = true): bool
    {
        app(Drafts::class)->put($this->draftKey(), SettingsTarget::class, ['writes' => $this->settingWrites($this->form->getState())], $this->getTitle(), static::getUrl());
        if ($notify) {
            Notification::make()->success()->title(__('Draft saved. Publish to show it on the website.'))->send();
        }

        return true;
    }

    /** What the screen edits, shown above the draft status. */
    abstract protected function screenDescription(): string;

    public function getSubheading(): ?string
    {
        return $this->screenDescription().' · '.(app(Drafts::class)->has($this->draftKey())
            ? __('There are saved changes that are not published yet')
            : __('The website shows this version'));
    }

    protected function getHeaderActions(): array
    {
        $drafts = app(Drafts::class);

        return [
            Action::make('save')->label(__('Save draft'))->icon('heroicon-o-check')->color('gray')->keyBindings(['mod+s'])->action(fn () => $this->save()),
            DraftActions::preview(fn () => $this->save(notify: false), fn () => $this->previewPath()),
            DraftActions::publish(function () use ($drafts) {
                $this->save(notify: false);
                $drafts->publish($this->draftKey());
                Notification::make()->success()->title(__('Published. The website now shows your changes.'))->send();
            }, fn () => $this->canPublish()),
            DraftActions::discard(function () use ($drafts) {
                $drafts->discard($this->draftKey());
                $this->mount();
                Notification::make()->success()->title(__('Changes discarded'))->send();
            }, fn () => $drafts->has($this->draftKey())),
        ];
    }

    private function draftKey(): string
    {
        return 'settings:'.class_basename(static::class);
    }
}
