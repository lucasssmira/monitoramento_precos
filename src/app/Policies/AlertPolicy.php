<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    /**
     * Determine whether the user can view the alert.
     */
    public function view(User $user, Alert $alert): bool
    {
        return $alert->user_id === $user->id;
    }

    /**
     * Determine whether the user can update or mark the alert as read.
     */
    public function update(User $user, Alert $alert): bool
    {
        return $alert->user_id === $user->id;
    }
}
