<?php

namespace App\Policies;

use App\Models\Garage;
use App\Models\User;

/*
  Autorización: estar logueado NO alcanza, hay que ser el dueño de ESA cochera.
  Laravel asocia esta Policy con el modelo Garage por convención (App\Policies\GaragePolicy).
 */
class GaragePolicy
{
    public function update(User $user, Garage $garage): bool
    {
        return (int) $user->id === (int) $garage->user_id;
    }

    public function delete(User $user, Garage $garage): bool
    {
        return (int) $user->id === (int) $garage->user_id;
    }
}
