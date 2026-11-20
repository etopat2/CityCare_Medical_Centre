<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('consultations.view') || $user->can('portal.visits.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Consultation $consultation): bool
    {
        if ($user->hasRole('doctor')) {
            return $consultation->doctor?->user_id === $user->id;
        }

        if ($user->hasRole('patient')) {
            return $consultation->patient?->user_id === $user->id;
        }

        return $user->can('consultations.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Appointment $appointment): bool
    {
        return $user->can('consultations.create') && $appointment->doctor?->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Consultation $consultation): bool
    {
        return $user->can('consultations.create') && $consultation->doctor?->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Consultation $consultation): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Consultation $consultation): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Consultation $consultation): bool
    {
        return false;
    }
}
