<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\CampaignUpdated;
use App\Notifications\CollaborationApplied;
use App\Notifications\CollaborationInvited;
use App\Notifications\CollaborationMessageReceived;
use Illuminate\Notifications\DatabaseNotification;

class NotificationInboxService
{
    /**
     * @return array{notifications: list<array<string, mixed>>, unread_count: int}
     */
    public function index(User $user): array
    {
        $notifications = $user->notifications()
            ->orderByRaw('read_at is null desc')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return [
            'notifications' => $notifications
                ->map(fn (DatabaseNotification $notification): array => $this->payload($notification))
                ->all(),
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function markRead(User $user, DatabaseNotification $notification): array
    {
        $this->ensureOwned($user, $notification);
        $notification->markAsRead();

        return $this->payload($notification->fresh() ?? $notification);
    }

    /**
     * @return array{notifications: list<array<string, mixed>>, unread_count: int}
     */
    public function markAllRead(User $user): array
    {
        $user->unreadNotifications->markAsRead();

        return $this->index($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'type' => $this->typeKey((string) $notification->type),
            'data' => $data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    private function typeKey(string $type): string
    {
        return match ($type) {
            CollaborationInvited::class => 'invite',
            CollaborationApplied::class => 'application',
            CampaignUpdated::class => 'campaign_update',
            CollaborationMessageReceived::class => 'message',
            default => class_basename($type),
        };
    }

    private function ensureOwned(User $user, DatabaseNotification $notification): void
    {
        if ($notification->notifiable_id !== $user->id || $notification->notifiable_type !== $user->getMorphClass()) {
            abort(404);
        }
    }
}
