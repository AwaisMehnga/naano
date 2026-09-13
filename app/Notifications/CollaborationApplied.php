<?php

namespace App\Notifications;

use App\Models\Collaboration;

class CollaborationApplied extends MarketplaceNotification
{
    public function __construct(public Collaboration $collaboration)
    {
        parent::__construct();
    }

    protected function emailPreferenceKey(): string
    {
        return 'applications';
    }

    /**
     * @return array{collaboration_id: int, campaign_id: int, campaign_name: string|null, title: string, body: string, href: string}
     */
    protected function payload(object $notifiable): array
    {
        $name = $this->collaboration->campaign->name;
        $creator = $this->collaboration->creatorProfile->display_name ?: 'A creator';

        return [
            'collaboration_id' => $this->collaboration->id,
            'campaign_id' => $this->collaboration->campaign_id,
            'campaign_name' => $name,
            'title' => 'New application',
            'body' => "{$creator} applied to {$name}.",
            'href' => '/campaigns/'.$this->collaboration->campaign_id,
        ];
    }
}
