<?php

namespace App\Notifications;

use App\Enums\ProfileType;
use App\Models\Collaboration;
use App\Models\User;

class CampaignUpdated extends MarketplaceNotification
{
    public function __construct(
        public Collaboration $collaboration,
        public string $title,
        public string $body,
    ) {}

    protected function emailPreferenceKey(): string
    {
        return 'campaign_updates';
    }

    /**
     * @return array{collaboration_id: int, campaign_id: int, campaign_name: string|null, title: string, body: string, href: string}
     */
    protected function payload(object $notifiable): array
    {
        $href = $notifiable instanceof User && $notifiable->ownsProfile(ProfileType::Creator)
            ? '/deals/'.$this->collaboration->id
            : '/campaigns/'.$this->collaboration->campaign_id;

        return [
            'collaboration_id' => $this->collaboration->id,
            'campaign_id' => $this->collaboration->campaign_id,
            'campaign_name' => $this->collaboration->campaign->name,
            'title' => $this->title,
            'body' => $this->body,
            'href' => $href,
        ];
    }
}
