<?php

namespace Database\Factories;

use App\Enums\OfferLabel;
use App\Models\CreatorOffer;
use App\Models\CreatorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreatorOffer>
 */
class CreatorOfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_profile_id' => CreatorProfile::factory(),
            'network' => 'linkedin',
            'label' => OfferLabel::SinglePost,
            'posts_count' => 1,
            'price_cents' => 24000,
            'is_active' => true,
        ];
    }
}
