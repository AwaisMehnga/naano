<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceService
{
    /**
     * @return array{email_invites: bool, email_applications: bool, email_campaign_updates: bool}
     */
    public function show(User $user): array
    {
        return $this->payload($this->preference($user));
    }

    /**
     * @param  array{email_invites: bool, email_applications: bool, email_campaign_updates: bool}  $data
     * @return array{email_invites: bool, email_applications: bool, email_campaign_updates: bool}
     */
    public function update(User $user, array $data): array
    {
        $preference = $this->preference($user);
        $preference->fill($data);
        $preference->save();

        return $this->payload($preference);
    }

    public function preference(User $user): NotificationPreference
    {
        return NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_invites' => true,
                'email_applications' => true,
                'email_campaign_updates' => true,
            ],
        );
    }

    /**
     * @return array{email_invites: bool, email_applications: bool, email_campaign_updates: bool}
     */
    private function payload(NotificationPreference $preference): array
    {
        return [
            'email_invites' => $preference->email_invites,
            'email_applications' => $preference->email_applications,
            'email_campaign_updates' => $preference->email_campaign_updates,
        ];
    }
}
