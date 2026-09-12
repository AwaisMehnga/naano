<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicDisk
{
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
