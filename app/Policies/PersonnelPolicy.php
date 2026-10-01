<?php
// app/Policies/PersonnelPolicy.php

namespace App\Policies;

use App\Models\Personnel;
use App\Models\User;

class PersonnelPolicy
{
    public function managePersonnel(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'staff'], true);
    }

    public function deletePersonnel(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin'], true);
    }

    public function viewPersonnel(User $user): bool
    {
        return true;
    }
}
