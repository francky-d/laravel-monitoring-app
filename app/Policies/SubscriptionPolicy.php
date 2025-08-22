<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any subscriptions.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own subscriptions
    }

    /**
     * Determine whether the user can view the subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        // User can view subscriptions for resources they own
        return $subscription->subscribable && $subscription->subscribable->user_id === $user->id;
    }

    /**
     * Determine whether the user can create subscriptions.
     */
    public function create(User $user): bool
    {
        return true; // Users can create subscriptions for their applications
    }

    /**
     * Determine whether the user can update the subscription.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        // User can update subscriptions for applications they own
        return $subscription->subscribable && $subscription->subscribable->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the subscription.
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        // User can delete subscriptions for applications they own
        return $subscription->subscribable && $subscription->subscribable->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the subscription.
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        return $subscription->subscribable && $subscription->subscribable->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the subscription.
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return $subscription->subscribable && $subscription->subscribable->user_id === $user->id;
    }
}
