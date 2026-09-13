<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class MarketplaceNotification extends Notification
{
    abstract protected function emailPreferenceKey(): string;

    /**
     * @return array{collaboration_id: int, campaign_id: int, campaign_name: string|null, title: string, body: string, href: string}
     */
    abstract protected function payload(object $notifiable): array;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->wantsEmail($this->emailPreferenceKey())) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->payload($notifiable);

        return (new MailMessage)
            ->subject($data['title'])
            ->line($data['body'])
            ->action('Open', url($data['href']));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload($notifiable);
    }
}
