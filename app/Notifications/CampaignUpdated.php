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
        public ?int $postId = null,
        public bool $mailable = true,
    ) {}

    protected function emailPreferenceKey(): string
    {
        return 'campaign_updates';
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (! $this->mailable) {
            return ['database'];
        }

        return parent::via($notifiable);
    }

    /**
     * @return array{collaboration_id: int, campaign_id: int, campaign_name: string|null, post_id: int|null, title: string, body: string, href: string}
     */
    protected function payload(object $notifiable): array
    {
        $isCreator = $notifiable instanceof User && $notifiable->ownsProfile(ProfileType::Creator);

        if ($isCreator) {
            $href = '/deals/'.$this->collaboration->id;
        } elseif ($this->postId !== null) {
            $href = '/campaigns/'.$this->collaboration->campaign_id.'/posts/'.$this->postId;
        } else {
            $href = '/campaigns/'.$this->collaboration->campaign_id;
        }

        return [
            'collaboration_id' => $this->collaboration->id,
            'campaign_id' => $this->collaboration->campaign_id,
            'campaign_name' => $this->collaboration->campaign->name,
            'post_id' => $this->postId,
            'title' => $this->title,
            'body' => $this->body,
            'href' => $href,
        ];
    }
}
