<?php

namespace Database\Factories;

use App\Enums\CollaborationSource;
use App\Enums\CollaborationStatus;
use App\Models\Campaign;
use App\Models\Collaboration;
use App\Models\CreatorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collaboration>
 */
class CollaborationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'creator_profile_id' => CreatorProfile::factory(),
            'source' => CollaborationSource::Invite,
            'status' => CollaborationStatus::Invited,
        ];
    }
}
