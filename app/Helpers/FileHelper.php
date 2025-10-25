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


