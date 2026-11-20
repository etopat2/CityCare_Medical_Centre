<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('patients.view') || $user->can('patients.treated.view') || $user->can('portal.profile.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient): bool
    {
        if ($user->hasRole('patient')) {
            return $patient->user_id === $user->id;
        }

        if ($user->hasRole('doctor')) {
            return $user->can('patients.treated.view')
                && $patient->consultations()->whereHas('doctor', fn ($query) => $query->where('user_id', $user->id))->exists();
        }

        return $user->can('patients.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('patients.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $user->can('patients.update') || ($user->hasRole('patient') && $patient->user_id === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin') && $user->can('patients.delete') && $patient->appointments()->doesntExist();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }
}
