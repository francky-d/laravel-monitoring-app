<?php

namespace App\Policies;

use App\Models\ApplicationGroup;
use App\Models\User;

class ApplicationGroupPolicy
{
    /**
     * Determine whether the user can view any application groups.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own application groups
    }

    /**
     * Determine whether the user can view the application group.
     */
    public function view(User $user, ApplicationGroup $applicationGroup): bool
    {
        // User can view application groups they own
        return $applicationGroup->user_id === $user->id;
    }

    /**
     * Determine whether the user can create application groups.
     */
    public function create(User $user): bool
    {
        return true; // Users can create their own application groups
    }

    /**
     * Determine whether the user can update the application group.
     */
    public function update(User $user, ApplicationGroup $applicationGroup): bool
    {
        // User can update application groups they own
        return $applicationGroup->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the application group.
     */
    public function delete(User $user, ApplicationGroup $applicationGroup): bool
    {
        // User can delete application groups they own
        return $applicationGroup->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the application group.
     */
    public function restore(User $user, ApplicationGroup $applicationGroup): bool
    {
        return $applicationGroup->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the application group.
     */
    public function forceDelete(User $user, ApplicationGroup $applicationGroup): bool
    {
        return $applicationGroup->user_id === $user->id;
    }
}
