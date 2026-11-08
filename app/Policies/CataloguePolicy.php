<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalogue records (services, regions, FAQ, statistics, testimonials, countries).
 * RULE-05: Editors work on drafts. Changes to a record on the website are kept as a draft (SavesTranslations),
 * so editors may make them; only content.publish (Content Manager, Super Admin) publishes or deletes.
 * Super Admin passes earlier through Gate::before.
 */
class CataloguePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('content.edit');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->can('content.edit');
    }

    public function create(User $user): bool
    {
        return $user->can('content.edit');
    }

    public function update(User $user, Model $record): bool
    {
        return $user->can('content.edit');
    }

    public function publish(User $user): bool
    {
        return $user->can('content.publish');
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->can('content.publish');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('content.publish');
    }
}
