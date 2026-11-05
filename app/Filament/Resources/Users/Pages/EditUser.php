<?php

namespace App\Filament\Resources\Users\Pages;

use App\Audit\Audit;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(fn () => ! UserResource::isProtected($this->getRecord()))
            ->before(fn () => abort_if(UserResource::isProtected($this->getRecord()), 403))];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->getRecord();
        $data['role'] = $user->roles->first()?->name;
        $data['is_active'] = $user->is_active;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $protected = UserResource::isProtected($record);
        $record->fill(['name' => $data['name'], 'email' => $data['email'], 'admin_locale' => $data['admin_locale']]);
        if (! $protected && array_key_exists('is_active', $data)) {
            $record->forceFill(['is_active' => (bool) $data['is_active']]);
        }
        $record->save();
        if (! $protected && filled($data['role'] ?? null) && ! $record->hasRole($data['role'])) {
            $record->syncRoles([$data['role']]);
            Audit::record('updated', $record, null, ['role']);
        }

        return $record;
    }
}
