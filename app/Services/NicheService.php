<?php

namespace App\Services;

use App\Models\Niche;

class NicheService
{
    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function active(): array
    {
        return Niche::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Niche $niche): array => [
                'id' => $niche->id,
                'name' => $niche->name,
                'slug' => $niche->slug,
            ])
            ->all();
    }
}
