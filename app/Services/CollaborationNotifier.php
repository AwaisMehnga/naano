<?php

namespace App\Services;

use App\Models\Collaboration;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CampaignUpdated;
use App\Notifications\CollaborationApplied;
use App\Notifications\CollaborationInvited;
use App\Notifications\CollaborationMessageReceived;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class CollaborationNotifier
{
    public function invited(Collaboration $collaboration, User $actor): void
    {
        $this->withContext($collaboration);
        $this->notifyCreator($collaboration, $actor, new CollaborationInvited($collaboration));
    }

    public function applied(Collaboration $collaboration, User $actor): void
    {
        $this->withContext($collaboration);
        $this->notifyCompany($collaboration, $actor, fn () => new CollaborationApplied($collaboration));
    }

    public function selected(Collaboration $collaboration, User $actor): void
    {
        $this->withContext($collaboration);
        $creator = $this->isCreator($actor, $collaboration);
        $name = $this->campaignName($collaboration);

        $this->campaignUpdated(
            $collaboration,
            $actor,
            $creator ? 'Invite accepted' : 'You were shortlisted',
            $creator
                ? $this->creatorName($collaboration)." accepted the invite on {$name}."
                : "You were shortlisted on {$name}.",
        );
    }

    public function booked(Collaboration $collaboration, User $actor): void
    {
        $this->campaignUpdated(
            $collaboration,
            $actor,
            'Deal booked',
            $this->campaignName($collaboration).' is booked.',
        );
    }

    public function cancelled(Collaboration $collaboration, User $actor): void
    {
        $this->campaignUpdated(
            $collaboration,
            $actor,
            'Collaboration cancelled',
            $this->campaignName($collaboration).' was cancelled.',
        );
    }

    public function declined(Collaboration $collaboration, User $actor): void
    {
        $this->campaignUpdated(
            $collaboration,
            $actor,
            'Invite declined',
            $this->creatorName($collaboration).' declined '.$this->campaignName($collaboration).'.',
        );
    }

    public function postSubmitted(Post $post, User $actor): void
    {
        $collaboration = $this->collaboration($post);
        $this->campaignUpdated(
            $collaboration,
            $actor,
            'Draft submitted for review',
            'A draft is waiting on '.$this->campaignName($collaboration).'.',
        );
    }

    public function postChangesRequested(Post $post, User $actor): void
    {
        $collaboration = $this->collaboration($post);
        $this->campaignUpdated(
            $collaboration,
            $actor,
            'Changes requested',
            'The brand requested changes on '.$this->campaignName($collaboration).'.',
        );
    }

    public function postApproved(Post $post, User $actor): void
    {
        $collaboration = $this->collaboration($post);
        $this->campaignUpdated(
            $collaboration,
            $actor,
            'Draft approved',
            'A draft was approved on '.$this->campaignName($collaboration).'.',
        );
    }

    public function postRejected(Post $post, User $actor): void
    {
        $collaboration = $this->collaboration($post);
        $this->campaignUpdated(
            $collaboration,
            $actor,
            'Draft rejected',
            'A draft was rejected on '.$this->campaignName($collaboration).'.',
        );
    }

    public function postPublished(Post $post, User $actor): void
    {
        $collaboration = $this->collaboration($post);
        $this->campaignUpdated(
            $collaboration,
            $actor,
            'Live URL submitted',
            'A live post URL was submitted on '.$this->campaignName($collaboration).'.',
        );
    }

    public function messageReceived(Collaboration $collaboration, User $actor, Message $message): void
    {
        $collaboration->loadMissing([
            'campaign:id,company_id,name',
            'campaign.company:id,user_id',
            'creatorProfile:id,user_id',
        ]);

        if ($this->isCreator($actor, $collaboration)) {
            NotificationFacade::send(
                $this->companyRecipients($collaboration, $actor),
                new CollaborationMessageReceived(
                    $collaboration,
                    $message,
                    '/campaigns/'.$collaboration->campaign_id,
                ),
            );

            return;
        }

        $creator = User::query()
            ->with('notificationPreference')
            ->find($collaboration->creatorProfile->user_id);

        if (! $creator instanceof User || $creator->id === $actor->id) {
            return;
        }

        $creator->notify(new CollaborationMessageReceived(
            $collaboration,
            $message,
            '/deals/'.$collaboration->id,
        ));
    }

    /**
     * @return Collection<int, User>
     */
    private function companyRecipients(Collaboration $collaboration, User $actor): Collection
    {
        $ownerId = $collaboration->campaign->company->user_id;

        return User::query()
            ->whereKey($ownerId)
            ->whereKeyNot($actor->id)
            ->with('notificationPreference')
            ->get();
    }

    public function campaignUpdated(Collaboration $collaboration, User $actor, string $title, string $body): void
    {
        $this->withContext($collaboration);
        $make = fn (): CampaignUpdated => new CampaignUpdated($collaboration, $title, $body);

        if ($this->isCreator($actor, $collaboration)) {
            $this->notifyCompany($collaboration, $actor, $make);

            return;
        }

        $this->notifyCreator($collaboration, $actor, $make());
    }

    private function collaboration(Post $post): Collaboration
    {
        $post->loadMissing('collaboration');

        return $post->collaboration;
    }

    private function withContext(Collaboration $collaboration): void
    {
        $collaboration->loadMissing(['campaign.company.user', 'creatorProfile.user']);
    }

    private function isCreator(User $actor, Collaboration $collaboration): bool
    {
        return $collaboration->creatorProfile->user_id === $actor->id;
    }

    private function campaignName(Collaboration $collaboration): string
    {
        $this->withContext($collaboration);

        return $collaboration->campaign->name;
    }

    private function creatorName(Collaboration $collaboration): string
    {
        $this->withContext($collaboration);

        return $collaboration->creatorProfile->display_name ?: 'A creator';
    }

    private function notifyCreator(Collaboration $collaboration, User $actor, Notification $notification): void
    {
        $user = $collaboration->creatorProfile->user;

        if ($user->id === $actor->id) {
            return;
        }

        $user->notify($notification);
    }

    /**
     * @param  callable(): Notification  $make
     */
    private function notifyCompany(Collaboration $collaboration, User $actor, callable $make): void
    {
        $owner = $collaboration->campaign->company->user;

        if (! $owner instanceof User || $owner->id === $actor->id) {
            return;
        }

        $owner->notify($make());
    }
}
