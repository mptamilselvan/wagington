<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('documentPreview')) {
    function documentPreview($path)
    {
        Log::info('DO_SPACES_URL: '.env('DO_SPACES_URL'));
        if (!$path) {
            return '<img src="https://placehold.co/80x80" alt="Preview" class="w-12 h-12 object-cover rounded">' ;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($extension, $imageExtensions)) {
            // Return actual image URL
            // return Storage::url($path);
            return '<img src="'.env('DO_SPACES_URL').'/'.$path.'" alt="Preview" class="w-12 h-12 object-cover rounded">';
            
        }

        if ($extension === 'pdf') {
            // Show PDF icon, link to actual PDF
            // dd("gxd");
            $icon = Storage::url('images/pdf-icon.png');
            return '<a href="'.env('DO_SPACES_URL').'/'.$path.'" target="_blank">
                    <div class="w-12 h-12 flex items-center justify-center bg-gray-200 rounded">
                    <i class="fa-solid fa-file"></i></div></a>';
        }

        // Return default icon if not an image
        return '<img src="https://placehold.co/80x80" alt="Preview" class="w-12 h-12 object-cover rounded">' ;
    }
}

if (!function_exists('hasSlotsChanged')) {
    function hasSlotsChanged($originalSlots, $newSlots)
    {
        // Normalize data for comparison
        $original = $originalSlots->map(fn($slot) => [
            'day'   => $slot->day,
            'start' => $slot->start_time,
            'end'   => $slot->end_time,
        ])->values()->toArray();

        $current = collect($newSlots)
            ->map(function ($slots, $day) {
                return collect($slots)->map(fn($slot) => [
                    'day'   => $day,
                    'start' => $slot['start'],
                    'end'   => $slot['end'],
                ])->toArray();
            })
            ->flatten(1)
            ->values()
            ->toArray();

        // Compare old vs new
        return $original !== $current;
    }
}

if (!function_exists('getFullUrl')) {
    function getFullUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return env('DO_SPACES_URL').'/'.$path;
    }
}