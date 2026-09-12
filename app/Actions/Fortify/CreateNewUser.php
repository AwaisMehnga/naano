<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::in(['creator', 'company'])],
            'hear_about' => ['required', 'string', Rule::in(array_keys(config('onboarding.hear_about')))],
        ])->validate();

        $user = DB::transaction(function () use ($input): User {
            $role = $input['role'];

            Role::findOrCreate($role, 'web');

            $user = User::create([
                'name' => $input['first_name'].' '.$input['last_name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'hear_about' => $input['hear_about'],
            ]);

            $user->assignRole($role);

            if ($role === 'creator') {
                $user->creatorProfile()->create([]);
            } else {
                $user->company()->create([]);
            }

            return $user;
        });

        $user->sendEmailVerificationNotification();

        return $user;
    }
}
