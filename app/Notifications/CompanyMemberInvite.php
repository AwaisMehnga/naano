<?php

namespace App\Notifications;

use App\Enums\CompanyMemberRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyMemberInvite extends Notification
{
    public function __construct(
        public Company $company,
        public User $inviter,
        public CompanyMemberRole $role,
        public string $email,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->company->name ?: 'a workspace';

        return (new MailMessage)
            ->subject("You're invited to join {$workspace}")
            ->line("{$this->inviter->name} invited you to join {$workspace} on ".config('app.name').'.')
            ->line('Create a company account with this email to join the team.')
            ->action('Create company account', route('register.company', ['email' => $this->email]));
    }
}
