<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Dealer;
use Illuminate\Auth\Access\HandlesAuthorization;

class DealerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Dealer');
    }

    public function view(AuthUser $authUser, Dealer $dealer): bool
    {
        return $authUser->can('View:Dealer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Dealer');
    }

    public function update(AuthUser $authUser, Dealer $dealer): bool
    {
        return $authUser->can('Update:Dealer');
    }

    public function delete(AuthUser $authUser, Dealer $dealer): bool
    {
        return $authUser->can('Delete:Dealer');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Dealer');
    }

    public function restore(AuthUser $authUser, Dealer $dealer): bool
    {
        return $authUser->can('Restore:Dealer');
    }

    public function forceDelete(AuthUser $authUser, Dealer $dealer): bool
    {
        return $authUser->can('ForceDelete:Dealer');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Dealer');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Dealer');
    }

    public function replicate(AuthUser $authUser, Dealer $dealer): bool
    {
        return $authUser->can('Replicate:Dealer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Dealer');
    }

}