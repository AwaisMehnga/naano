<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Enums\ProfileType;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private ProfileService $profiles) {}

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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->whereNotNull('email_verified_at'),
            ],
            'password' => $this->passwordRules(),
            'profile' => ['sometimes', 'nullable', Rule::in(['creator', 'company'])],
            'role' => ['sometimes', 'nullable', Rule::in(['creator', 'company'])],
            'hear_about' => ['required', 'string', Rule::in(array_keys(config('onboarding.hear_about')))],
        ], [
            'email.unique' => 'This email is already registered. Sign in or reset your password.',
        ])->validate();

        $email = $input['email'];

        return Cache::lock('register:'.$email, 15)->block(10, function () use ($input, $email): User {
            return DB::transaction(function () use ($input, $email): User {
                $user = User::query()
                    ->where('email', $email)
                    ->whereNull('email_verified_at')
                    ->lockForUpdate()
                    ->first();

                $name = $input['first_name'].' '.$input['last_name'];

                if ($user === null) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => $input['password'],
                        'hear_about' => $input['hear_about'],
                    ]);
                } else {
                    $user->fill([
                        'name' => $name,
                        'password' => $input['password'],
                        'hear_about' => $input['hear_about'],
                    ])->save();
                }

                $profile = $input['profile'] ?? $input['role'] ?? null;

                if (is_string($profile) && $profile !== '') {
                    $type = ProfileType::from($profile);
                    $this->profiles->provision($user, $type);
                }

                return $user->fresh(['creatorProfile', 'company']);
            });
        });
    }
}
