<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Determine whether the user can view any incidents.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own incidents
    }

    /**
     * Determine whether the user can view the incident.
     */
    public function view(User $user, Incident $incident): bool
    {
        // User can view incidents for applications they own
        return $incident->application->user_id === $user->id;
    }

    /**
     * Determine whether the user can create incidents.
     */
    public function create(User $user): bool
    {
        return true; // Users can create incidents for their applications
    }

    /**
     * Determine whether the user can update the incident.
     */
    public function update(User $user, Incident $incident): bool
    {
        // User can update incidents for applications they own
        return $incident->application->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the incident.
     */
    public function delete(User $user, Incident $incident): bool
    {
        // User can delete incidents for applications they own
        return $incident->application->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the incident.
     */
    public function restore(User $user, Incident $incident): bool
    {
        return $incident->application->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the incident.
     */
    public function forceDelete(User $user, Incident $incident): bool
    {
        return $incident->application->user_id === $user->id;
    }
}
