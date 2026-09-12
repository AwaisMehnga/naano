<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * @return array{id: int, name: string, email: string, email_verified_at: mixed, role: string|null}
     */
    public function current(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'role' => $user->side(),
        ];
    }
}
