<?php

namespace App\Notifications;

use App\Models\Collaboration;
use App\Models\Message;
use Illuminate\Support\Str;

class CollaborationMessageReceived extends MarketplaceNotification
{
    public function __construct(
        public Collaboration $collaboration,
        public Message $message,
        public string $href,
    ) {}

    protected function emailPreferenceKey(): string
    {
        return 'messages';
    }

    /**
     * @return array{collaboration_id: int, campaign_id: int, campaign_name: string|null, title: string, body: string, href: string}
     */
    protected function payload(object $notifiable): array
    {
        return [
            'collaboration_id' => $this->collaboration->id,
            'campaign_id' => $this->collaboration->campaign_id,
            'campaign_name' => $this->collaboration->campaign->name,
            'title' => 'New message',
            'body' => Str::limit($this->message->body, 140),
            'href' => $this->href,
        ];
    }
}
