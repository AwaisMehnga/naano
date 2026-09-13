<?php

namespace App\Services;

use App\Enums\CollaborationStatus;
use App\Models\Collaboration;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CollaborationMessageService
{
    public function __construct(private CollaborationNotifier $notifier) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function indexForCompany(Company $company, Collaboration $collaboration): array
    {
        $this->ensureCompanyOwned($company, $collaboration);

        return $this->thread($collaboration);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function indexForCreator(User $user, Collaboration $collaboration): array
    {
        $this->ensureCreatorOwned($user, $collaboration);

        return $this->thread($collaboration);
    }

    /**
     * @return array<string, mixed>
     */
    public function storeForCompany(Company $company, User $actor, Collaboration $collaboration, string $body): array
    {
        $this->ensureCompanyOwned($company, $collaboration);
        $this->ensureCanSend($collaboration);

        return $this->store($actor, $collaboration, $body);
    }

    /**
     * @return array<string, mixed>
     */
    public function storeForCreator(User $user, Collaboration $collaboration, string $body): array
    {
        $this->ensureCreatorOwned($user, $collaboration);
        $this->ensureCanSend($collaboration);

        return $this->store($user, $collaboration, $body);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function markReadForCompany(Company $company, User $actor, Collaboration $collaboration): array
    {
        $this->ensureCompanyOwned($company, $collaboration);

        return $this->markRead($actor, $collaboration);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function markReadForCreator(User $user, Collaboration $collaboration): array
    {
        $this->ensureCreatorOwned($user, $collaboration);

        return $this->markRead($user, $collaboration);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function thread(Collaboration $collaboration): array
    {
        $conversation = $this->conversation($collaboration);

        return $conversation->messages()
            ->with('author')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message): array => $this->payload($message))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function store(User $actor, Collaboration $collaboration, string $body): array
    {
        $conversation = $this->conversation($collaboration);

        $message = $conversation->messages()->create([
            'author_user_id' => $actor->id,
            'body' => $body,
        ]);

        dispatch(fn () => $this->notifier->messageReceived($collaboration, $actor, $message))->afterResponse();

        return [
            'id' => $message->id,
            'author' => [
                'id' => $actor->id,
                'name' => $actor->name,
            ],
            'body' => $message->body,
            'read_at' => null,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function markRead(User $user, Collaboration $collaboration): array
    {
        $conversation = $this->conversation($collaboration);

        $conversation->messages()
            ->where('author_user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->thread($collaboration);
    }

    private function conversation(Collaboration $collaboration): Conversation
    {
        $existing = $collaboration->conversation;

        if ($existing instanceof Conversation) {
            return $existing;
        }

        return $collaboration->conversation()->firstOrCreate([]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Message $message): array
    {
        return [
            'id' => $message->id,
            'author' => [
                'id' => $message->author->id,
                'name' => $message->author->name,
            ],
            'body' => $message->body,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    private function ensureCanSend(Collaboration $collaboration): void
    {
        if ($collaboration->status === CollaborationStatus::Cancelled) {
            throw ValidationException::withMessages([
                'body' => 'This collaboration can no longer receive messages.',
            ]);
        }
    }

    private function ensureCompanyOwned(Company $company, Collaboration $collaboration): void
    {
        $collaboration->loadMissing('campaign');

        if ($collaboration->campaign->company_id !== $company->id) {
            abort(404);
        }
    }

    private function ensureCreatorOwned(User $user, Collaboration $collaboration): void
    {
        $profile = $user->creatorProfile;

        if ($profile === null || $collaboration->creator_profile_id !== $profile->id) {
            abort(404);
        }
    }
}
