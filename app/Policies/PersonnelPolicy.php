<?php
// app/Policies/PersonnelPolicy.php

namespace App\Policies;

use App\Models\Personnel;
use App\Models\User;

class PersonnelPolicy
{
    public function managePersonnel(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }

    public function deletePersonnel(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function viewPersonnel(User $user): bool
    {
        return true;
    }
}