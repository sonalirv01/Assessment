<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class CataloguePolicy
{
    public function create(User $user): Response
    {
        return $user->canManageCatalogue()
            ? Response::allow()
            : Response::deny('This action is restricted to admin or manager users.');
    }

    public function update(User $user): Response
    {
        return $user->canManageCatalogue()
            ? Response::allow()
            : Response::deny('This action is restricted to admin or manager users.');
    }

    public function delete(User $user): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('This action is restricted to admin users.');
    }
}
