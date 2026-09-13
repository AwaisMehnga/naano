<?php

namespace App\Notifications;

use App\Models\Collaboration;

class CollaborationInvited extends MarketplaceNotification
{
    public function __construct(public Collaboration $collaboration)
    {
        parent::__construct();
    }

    protected function emailPreferenceKey(): string
    {
        return 'invites';
    }

    /**
     * @return array{collaboration_id: int, campaign_id: int, campaign_name: string|null, title: string, body: string, href: string}
     */
    protected function payload(object $notifiable): array
    {
        $name = $this->collaboration->campaign->name;

        return [
            'collaboration_id' => $this->collaboration->id,
            'campaign_id' => $this->collaboration->campaign_id,
            'campaign_name' => $name,
            'title' => 'Campaign invite',
            'body' => "You've been invited to {$name}.",
            'href' => '/deals/'.$this->collaboration->id,
        ];
    }
}
