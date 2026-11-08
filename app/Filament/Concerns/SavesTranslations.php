<?php

namespace App\Filament\Concerns;

use App\Content\RecordWriter;
use App\Drafts\Drafts;
use App\Drafts\Targets\RecordTarget;
use App\Filament\Support\DraftActions;
use App\Models\Contracts\Publishable;
use App\Translation\ContentTranslator;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * For Create/Edit pages of models using App\Models\Concerns\HasTranslations.
 * Fills `translations.{locale}.*` from the translation rows and writes them back with RecordWriter.
 *
 * On an Edit page of a record visitors can see (Publishable and live), Save keeps the changes as a draft:
 * nothing changes on the website until Publish. A record that is not on the website is saved directly.
 */
trait SavesTranslations
{
    /** Set when the last save went to the draft, for the notification. */
    protected bool $savedAsDraft = false;

    /** Many-to-many lists in the form (plain fields, written by RecordWriter so they can wait in a draft). */
    protected function formRelations(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if ($draft = app(Drafts::class)->find(Drafts::recordKey($record))) {
            return [...$data, ...$draft->payload['data']];
        }
        $data['translations'] = $record->loadMissing('translations')->translations
            ->mapWithKeys(fn (Model $t) => [$t->locale => [...$t->attributesToArray(), 'review_status' => $t->review_status->value]])
            ->all();
        foreach ($this->formRelations() as $relation) {
            $data[$relation] = $record->{$relation}()->pluck($record->{$relation}()->getRelated()->getQualifiedKeyName())->all();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $writer = app(RecordWriter::class);
        $record = $writer->create(static::getModel(), $data, $this->formRelations());
        $writer->translateEmptyLanguagesLater($record);

        return $record;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $this->savedAsDraft = $this->keepsDrafts($record);
        if ($this->savedAsDraft) {
            app(Drafts::class)->put(Drafts::recordKey($record), RecordTarget::class,
                ['model' => $record::class, 'id' => $record->getKey(), 'data' => $data, 'relations' => $this->formRelations()],
                static::getResource()::getModelLabel().': '.static::getResource()::getRecordTitle($record),
                static::getResource()::getUrl('edit', ['record' => $record]));

            return $record;
        }
        $writer = app(RecordWriter::class);
        $writer->update($record, $data, $this->formRelations());
        $writer->translateEmptyLanguagesLater($record);

        return $record;
    }

    protected function getSaveFormAction(): Action
    {
        $action = parent::getSaveFormAction();

        return $this->getRecord() && $this->keepsDrafts($this->getRecord()) ? $action->label(__('Save draft')) : $action;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->savedAsDraft ? __('Draft saved. Publish to show it on the website.') : __('Saved');
    }

    public function getSubheading(): ?string
    {
        if (! $this->getRecord() instanceof Publishable) {
            return null;
        }

        return match (true) {
            $this->hasDraft() => __('There are saved changes that are not published yet'),
            $this->getRecord()->isLive() => __('On the website. Changes wait as a draft until you publish them.'),
            default => __('Not on the website yet'),
        };
    }

    /** Preview, Publish and Discard for records visitors can see. @return list<Action> */
    protected function draftActions(): array
    {
        $record = fn () => $this->getRecord();
        if (! $record() instanceof Publishable) {
            return [];
        }

        return [
            DraftActions::preview(fn () => $this->saveQuietly(), fn () => $record()->sitePath()),
            DraftActions::publish(function () {
                if ($this->saveQuietly() === false) {
                    return;
                }
                $record = $this->getRecord();
                app(Drafts::class)->publish(Drafts::recordKey($record));
                app(RecordWriter::class)->translateEmptyLanguagesLater($record);
                $this->fillForm();
                Notification::make()->success()->title(__('Published. The website now shows your changes.'))->send();
            }, fn () => $this->keepsDrafts($record()) && auth()->user()?->can('content.publish')),
            DraftActions::discard(function () {
                app(Drafts::class)->discard(Drafts::recordKey($this->getRecord()));
                $this->fillForm();
                Notification::make()->success()->title(__('Changes discarded'))->send();
            }, fn () => $this->hasDraft()),
        ];
    }

    /**
     * Runs a change that writes to the record (machine translation) on the draft when the record is live, so it too
     * waits for Publish. Returns the change's result.
     */
    public function changeRecord(Closure $change): mixed
    {
        $record = $this->getRecord();
        if (! $this->keepsDrafts($record)) {
            return $change($record);
        }
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
        $drafts = app(Drafts::class);
        $key = Drafts::recordKey($record);
        [$result, $translations] = $drafts->peek($key, function () use ($record, $change) {
            $fresh = $record->fresh();

            return [$change($fresh), $fresh->translations()->get()
                ->mapWithKeys(fn (Model $t) => [$t->locale => [...$t->attributesToArray(), 'review_status' => $t->review_status->value]])->all()];
        });
        $draft = $drafts->find($key);
        $drafts->put($key, RecordTarget::class, [...$draft->payload, 'data' => [...$draft->payload['data'], 'translations' => $translations]], $draft->label, $draft->edit_url);
        $this->fillForm();

        return $result;
    }

    public function translateMissingLanguages(): int
    {
        return $this->changeRecord(fn (Model $record) => app(ContentTranslator::class)->translateRecord($record));
    }

    private function keepsDrafts(Model $record): bool
    {
        return $record instanceof Publishable && $record->isLive();
    }

    private function hasDraft(): bool
    {
        return app(Drafts::class)->has(Drafts::recordKey($this->getRecord()));
    }

    /** Saves without a notification or redirect (a validation error stops the action and shows on the form). */
    private function saveQuietly(): bool
    {
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        return true;
    }
}
