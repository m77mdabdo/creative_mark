<?php

namespace App\Policies;

use App\Models\ConsultationRequest;
use App\Models\User;

/**
 * Consultation bookings (PermissionMatrix): sales managers see and assign everything; sales agents and
 * consultants see and answer only bookings assigned to them. Bookings are created only by the public form.
 * Deleting personal data is a privacy request (Super Admin, through Gate::before).
 */
class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view');
    }

    public function view(User $user, ConsultationRequest $booking): bool
    {
        return $user->can('consultations.view') && $this->owns($user, $booking);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ConsultationRequest $booking): bool
    {
        return $user->can('consultations.update') && $this->owns($user, $booking);
    }

    public function reply(User $user, ConsultationRequest $booking): bool
    {
        return $this->update($user, $booking);
    }

    public function assign(User $user): bool
    {
        return $user->can('leads.assign');
    }

    public function delete(User $user, ConsultationRequest $booking): bool
    {
        return $user->can('privacy.requests');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('privacy.requests');
    }

    private function owns(User $user, ConsultationRequest $booking): bool
    {
        return $user->can('leads.assign') || $booking->assigned_to === $user->id;
    }
}
