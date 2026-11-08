<?php

namespace App\Drafts\Targets;

use App\Content\RecordWriter;
use App\Drafts\DraftTarget;
use Illuminate\Database\Eloquent\Model;

/** A dashboard form's data for one record: payload {model, id, data, relations}. */
final class RecordTarget implements DraftTarget
{
    public function __construct(private readonly RecordWriter $writer) {}

    public function apply(array $payload): void
    {
        /** @var class-string<Model> $model */
        $model = $payload['model'];
        $record = $model::query()->find($payload['id']);
        // The record was deleted after the draft was saved: nothing to publish.
        if ($record) {
            $this->writer->update($record, $payload['data'], $payload['relations'] ?? []);
        }
    }
}
