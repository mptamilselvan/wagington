<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Exception;

class ImageService
{
    // Storage configuration constants
    private const DISK_DIGITALOCEAN = 'do_spaces';
    
    // Directory constants
    private const DIR_IMAGES = 'images';
    private const DIR_CUSTOMERS = 'profile';
    private const DIR_ECOMMERCE = 'e-commerce'; // for product images
    
    // File constants
    private const DEFAULT_AVATAR = 'images/default-avatar.svg';
    private const DEFAULT_INITIAL = 'U';
    
    // Avatar colors
    private const AVATAR_COLORS = [
        'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-pink-500',
        'bg-indigo-500', 'bg-red-500', 'bg-yellow-500', 'bg-teal-500',
        'bg-orange-500', 'bg-cyan-500'
    ];
    
    // Image return types
    private const TYPE_INITIALS = 'initials';
    /**
     * Get the preferred storage disk for images
     */
    public static function getPreferredDisk(): string
    {
        return self::DISK_DIGITALOCEAN;
    }





    /**
     * Lightweight storage health check for prod readiness
     * - Verifies preferred disk can list root or target directory
     * - Returns true if disk is reachable, false otherwise
     */
    public static function storageHealthCheck(?string $directory = null): bool
    {
        try {
            $disk = self::getPreferredDisk();
            $dir = $directory ?: '/';
            // Attempt a harmless list operation
            Storage::disk($disk)->files($dir);
            return true;
        } catch (\Throwable $e) {
            \Log::error('Storage health check failed', [
                'disk' => self::getPreferredDisk(),
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the full URL for an image
     */
    public static function getImageUrl(?string $imagePath, ?string $defaultImage = null, ?string $firstName = null, ?string $lastName = null)
    {
        try {
            // Handle empty image path
            if (empty($imagePath)) {
                return self::handleEmptyImagePath($firstName, $lastName, $defaultImage);
            }

            // Handle full URLs
            if (self::isFullUrl($imagePath)) {
                return $imagePath;
            }

            // Try to get URL from storage
            $url = self::getStorageUrl($imagePath);
            if ($url) {
                return $url;
            }
            
            // If we have a defaultImage URL, try to use it before falling back to avatar
            if ($defaultImage && self::isFullUrl($defaultImage)) {
                \Log::info('ImageService: Using defaultImage as fallback', ['defaultImage' => $defaultImage]);
                return $defaultImage;
            }
            // Fallback to initials or default
            return self::getFallbackImage($firstName, $lastName, $defaultImage);
            
        } catch (Exception $e) {
            return self::getFallbackImage($firstName, $lastName, $defaultImage);
        }
    }

    /**
     * Handle empty image path scenario
     */
    private static function handleEmptyImagePath(?string $firstName, ?string $lastName, ?string $defaultImage)
    {
        if ($firstName || $lastName) {
            return self::generateInitialsData($firstName, $lastName);
        }
        
        return $defaultImage ?: asset(self::DEFAULT_AVATAR);
    }

    /**
     * Check if path is a full URL
     */
    private static function isFullUrl(string $path): bool
    {
        return str_starts_with($path, 'http');
    }

        /**
     * Extract storage path from full URL
     */
    private static function extractPathFromUrl(string $url): ?string
    {
        try {
            // First check if it's actually a URL
            if (!self::isFullUrl($url)) {
                return null;
            }
            
            $parsedUrl = parse_url($url);
            if (!$parsedUrl || !isset($parsedUrl['path'])) {
                return null;
            }

            $path = $parsedUrl['path'];
            
            // For Digital Ocean Spaces URLs, remove the leading slash and bucket prefix if present
            // Example: https://your-space.digitaloceanspaces.com/e-commerce/... -> e-commerce/...
            $path = ltrim($path, '/');
            
            // For local storage URLs, remove the 'storage/' prefix if present
            // Example: http://localhost/storage/e-commerce/... -> e-commerce/...
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8); // Remove 'storage/'
            }
            
            return $path ?: null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get URL from storage disks
     */
    private static function getStorageUrl(string $imagePath): ?string
    {
        if (Storage::disk(self::DISK_DIGITALOCEAN)->exists($imagePath)) {
            return Storage::disk(self::DISK_DIGITALOCEAN)->url($imagePath);
        }
        return null;
    }



    /**
     * Get fallback image (initials or default)
     */
    private static function getFallbackImage(?string $firstName, ?string $lastName, ?string $defaultImage)
    {
        if ($firstName || $lastName) {
            return self::generateInitialsData($firstName, $lastName);
        }
        
        return $defaultImage ?: asset(self::DEFAULT_AVATAR);
    }

    /**
     * Generate initials data array
     */
    private static function generateInitialsData(?string $firstName, ?string $lastName): array
    {
        return [
            'type' => self::TYPE_INITIALS,
            'initials' => self::generateInitials($firstName, $lastName),
            'color' => self::generateColorFromName($firstName, $lastName)
        ];
    }

    /**
     * Generate initials from first and last name
     */
    public static function generateInitials(?string $firstName, ?string $lastName): string
    {
        $initials = '';
        
        if ($firstName) {
            $initials .= strtoupper(substr(trim($firstName), 0, 1));
        }
        
        if ($lastName) {
            $initials .= strtoupper(substr(trim($lastName), 0, 1));
        }
        
        return empty($initials) ? self::DEFAULT_INITIAL : $initials;
    }

    /**
     * Generate a consistent color based on the name
     */
    public static function generateColorFromName(?string $firstName, ?string $lastName): string
    {
        $name = trim(($firstName ?? '') . ($lastName ?? ''));
        
        if (empty($name)) {
            return self::AVATAR_COLORS[0]; // Default color
        }
        
        $hash = crc32($name);
        $index = abs($hash) % count(self::AVATAR_COLORS);
        
        return self::AVATAR_COLORS[$index];
    }

    /**
     * Upload an image to the best available storage disk
     */
    public static function uploadImage($file, string $directory = self::DIR_IMAGES, ?string $filename = null): ?string
    {
        if (!$file) {
            return null;
        }

        try {
            return self::performUpload($file, $directory, $filename, self::DISK_DIGITALOCEAN);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Perform the actual file upload with retry and logging (no disk fallback)
     */
    private static function performUpload($file, string $directory, ?string $filename, string $disk): ?string
    {
        $attempts = 0;
        $maxAttempts = 3;
        $delayMs = 150;

        // For DO, set public visibility at creation time
        // Always DO Spaces with public visibility
        $options = ['disk' => self::DISK_DIGITALOCEAN, 'visibility' => 'public'];

        while ($attempts < $maxAttempts) {
            try {
                return $filename
                    ? $file->storeAs($directory, $filename, $options)
                    : $file->store($directory, $options);
            } catch (\Throwable $e) {
                $attempts++;
                \Log::warning('Image upload attempt failed', [
                    'attempt' => $attempts,
                    'max_attempts' => $maxAttempts,
                    'disk' => $disk,
                    'directory' => $directory,
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ]);

                if ($attempts >= $maxAttempts) {
                    \Log::error('Image upload failed after max attempts', [
                        'disk' => $disk,
                        'directory' => $directory,
                        'filename' => $filename,
                        'exception' => $e,
                    ]);
                    throw $e;
                }

                usleep($delayMs * 1000);
                $delayMs *= 2;
            }
        }

        return null;
    }

    /**
     * Upload customer profile image with organized folder structure
     */
    public static function uploadCustomerProfileImage($file, int $customerId, ?string $filename = null): ?string
    {
        if (!$file) {
            return null;
        }

        try {
            $directory = self::DIR_CUSTOMERS . "/user_{$customerId}"; // profile/user_{userId}
            $preferredDisk = self::DISK_DIGITALOCEAN;

            // Use timestamped filename per docs: profile_<timestamp>.<ext>
            $extension = null;
            if (method_exists($file, 'getClientOriginalExtension')) {
                $extension = $file->getClientOriginalExtension();
            }
            if (!$extension && method_exists($file, 'extension')) {
                // Fallback to mime-guessing extension
                $extension = $file->extension();
            }
            $extension = strtolower($extension ?: 'jpg');
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
            $finalFilename = $filename ?: ('profile_' . time() . '.' . $extension);

            // Store (may fallback internally to public if DO fails)
            $path = self::performUpload($file, $directory, $finalFilename, $preferredDisk);

            if ($path) {
                self::purgeCustomerProfileImages($customerId, $path);

                // Return full URL from DO Spaces
                return Storage::disk(self::DISK_DIGITALOCEAN)->url($path);
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Purge existing customer profile images (image.*) except the provided path
     */
    private static function purgeCustomerProfileImages(int $customerId, ?string $exceptPath = null): void
    {
        try {
            $directory = self::DIR_CUSTOMERS . "/user_{$customerId}";
            $normalizedExcept = $exceptPath ? ltrim($exceptPath, '/') : null;

            $files = [];
            try { $files = Storage::disk(self::DISK_DIGITALOCEAN)->files($directory); } catch (\Throwable $t) {}
            foreach ($files as $file) {
                if ($normalizedExcept && ltrim($file, '/') === $normalizedExcept) {
                    continue;
                }
                if (preg_match('/\/profile_.*\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                    try { Storage::disk(self::DISK_DIGITALOCEAN)->delete($file); } catch (\Throwable $t) {}
                }
            }
        } catch (Exception $e) {
            // Ignore cleanup failures
        }
    }





    /**
     * Delete customer profile image
     */
    public static function deleteCustomerProfileImage(?string $imagePath, int $customerId): bool
    {
        if (empty($imagePath)) {
            return true;
        }

        try {
            // If a full URL is stored, convert to storage key
            if (self::isFullUrl($imagePath)) {
                $imagePath = self::extractStoragePathFromUrl($imagePath);
            }

            $deleted = false;
            try { $deleted = Storage::disk(self::DISK_DIGITALOCEAN)->delete($imagePath) || $deleted; } catch (\Throwable $t) {}

            return $deleted;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Extract storage key from a full URL for DO Spaces
     */
    private static function extractStoragePathFromUrl(string $url): string
    {
        $path = ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        // If path-style URL includes bucket in path, strip it
        $bucket = config('filesystems.disks.do_spaces.bucket');
        if ($bucket && str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }
        return $path;
    }

    /**
     * Delete an image from storage
     */
    public static function deleteImage(?string $imagePath): bool
    {
        if (self::shouldSkipDeletion($imagePath)) {
            return true;
        }

        try {
            $deleted = false;
            try { $deleted = Storage::disk(self::DISK_DIGITALOCEAN)->delete($imagePath) || $deleted; } catch (\Throwable $t) {}
            return $deleted;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if image deletion should be skipped
     */
    private static function shouldSkipDeletion(?string $imagePath): bool
    {
        return empty($imagePath) || self::isFullUrl($imagePath);
    }

    /**
     * Check if an image exists in storage
     */
    public static function imageExists(?string $imagePath): bool
    {
        if (empty($imagePath)) {
            return false;
        }

        // Full URLs are assumed to exist
        if (self::isFullUrl($imagePath)) {
            return true;
        }

        try {
            return Storage::disk(self::DISK_DIGITALOCEAN)->exists($imagePath);
        } catch (Exception $e) {
            return false;
        }
    }
}