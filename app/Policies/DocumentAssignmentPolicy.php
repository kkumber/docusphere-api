<?php

namespace App\Policies;

use App\Models\DocumentAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentAssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }


    public function assign(User $user, User $target): bool
    {
        // Admin can assign to anyone
        if ($user->hasRole('admin')) {
            return true;
        }

        // Records can assign ONLY to SDS
        if ($user->hasRole('records')) {
            return $target->hasRole('sds');
        }

        // SDS can assign to anyone
        if ($user->hasRole('sds')) {
            return true;
        }

        // Chiefs and Staff cannot assign to Records
        if ($target->hasRole('records')) {
            return false;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DocumentAssignment $documentAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DocumentAssignment $documentAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DocumentAssignment $documentAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DocumentAssignment $documentAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DocumentAssignment $documentAssignment): bool
    {
        return false;
    }
}
