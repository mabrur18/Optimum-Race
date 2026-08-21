<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Superadmin can view all events
        if ($user->isSuperadmin()) {
            return true;
        }

        // Admin and Staff can view events from their organizer
        if ($user->hasOrganizerAccess()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        // Superadmin can view any event
        if ($user->isSuperadmin()) {
            return true;
        }

        // Admin and Staff can only view events from their organizer
        if ($user->hasOrganizerAccess()) {
            return $event->organizer_id === $user->organizer_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Superadmin can create events for any organizer
        if ($user->isSuperadmin()) {
            return true;
        }

        // Admin can create events for their organizer
        if ($user->isAdmin()) {
            return true;
        }

        // Staff cannot create events
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        // Superadmin can update any event
        if ($user->isSuperadmin()) {
            return true;
        }

        // Admin can update events from their organizer
        if ($user->isAdmin()) {
            return $event->organizer_id === $user->organizer_id;
        }

        // Staff cannot update events
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        // Superadmin can delete any event
        if ($user->isSuperadmin()) {
            return true;
        }

        // Admin can delete events from their organizer
        if ($user->isAdmin()) {
            return $event->organizer_id === $user->organizer_id;
        }

        // Staff cannot delete events
        return false;
    }
}
