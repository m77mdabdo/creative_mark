<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** The account starts with an unknown random password; the invitation link lets its owner choose one. */
    protected function handleRecordCreation(array $data): Model
    {
        $user = new User;
        $user->fill(['name' => $data['name'], 'email' => $data['email'], 'admin_locale' => $data['admin_locale'], 'password' => Str::password(40)]);
        $user->forceFill(['is_active' => (bool) ($data['is_active'] ?? true)])->save();
        $user->syncRoles([$data['role']]);

        return $user;
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->getRecord();
        UserResource::sendAccessLink($user, 'invited')
            ? Notification::make()->success()->title(__('Invitation sent to :email', ['email' => $user->email]))->send()
            : Notification::make()->danger()->title(__('The account was created but the invitation could not be sent. Use “Send password link”.'))->persistent()->send();
    }
}
