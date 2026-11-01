<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalogue records (services, regions, FAQ, statistics, testimonials, countries).
 * RULE-05: Editors work on drafts; only content.publish (Content Manager, Super Admin) may change
 * a published record, publish one, or delete. Super Admin passes earlier through Gate::before.
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
        return $user->can('content.publish') || ($user->can('content.edit') && ! $this->isLive($record));
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

    private function isLive(Model $record): bool
    {
        // Records without a status column (statistics) are always live, so only publishers may edit them.
        return ($record->getAttributes()['status'] ?? 'published') === 'published';
    }
}
