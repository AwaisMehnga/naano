<?php

namespace App\Notifications;

use App\Models\Collaboration;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Str;

class CollaborationMessageReceived extends MarketplaceNotification
{
    public function __construct(
        public Collaboration $collaboration,
        public Message $message,
    ) {
        parent::__construct();
    }

    protected function emailPreferenceKey(): string
    {
        return 'messages';
    }

    /**
     * @return array{collaboration_id: int, campaign_id: int, campaign_name: string|null, title: string, body: string, href: string}
     */
    protected function payload(object $notifiable): array
    {
        $name = $this->collaboration->campaign->name;
        $href = $notifiable instanceof User && $notifiable->hasRole('creator')
            ? '/deals/'.$this->collaboration->id
            : '/campaigns/'.$this->collaboration->campaign_id;

        return [
            'collaboration_id' => $this->collaboration->id,
            'campaign_id' => $this->collaboration->campaign_id,
            'campaign_name' => $name,
            'title' => 'New message',
            'body' => Str::limit($this->message->body, 140),
            'href' => $href,
        ];
    }
}
