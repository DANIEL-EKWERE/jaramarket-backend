<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Get the active storage disk from settings.
 * Falls back to env FILESYSTEM_DISK, then 'public'.
 */
function active_storage_disk(): string
{
    return Cache::remember('storage_disk', 300, function () {
        try {
            return DB::table('settings')->where('key', 'storage_disk')->value('value')
                   ?? env('FILESYSTEM_DISK', 'public');
        } catch (\Throwable $e) {
            return env('FILESYSTEM_DISK', 'public');
        }
    });
}

/**
 * Upload an image to the active storage disk.
 */
function upload_image(string $folder, $image_file, ?string $old_file = null): ?string
{
    if (!$image_file) return null;

    $disk     = active_storage_disk();
    $filename = time() . '_' . preg_replace('/\s+/', '_', $image_file->getClientOriginalName());

    try {
        // Delete old file if exists
        if ($old_file && Storage::disk($disk)->exists($old_file)) {
            Storage::disk($disk)->delete($old_file);
        }

        $path = $image_file->storeAs($folder, $filename, $disk);
        return $path;

    } catch (\Exception $e) {
        \Log::error('Image upload failed', [
            'disk'  => $disk,
            'file'  => $filename,
            'error' => $e->getMessage(),
        ]);
        return null;
    }
}

/**
 * Delete an image from the active storage disk.
 */
function delete_image(?string $path): bool
{
    if (!$path) return false;

    $disk = active_storage_disk();

    try {
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
            return true;
        }
        return false;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Get the public URL for a stored file.
 */
function get_media_url(?string $path): ?string
{
    if (!$path) return null;

    $disk = active_storage_disk();

    try {
        if (!Storage::disk($disk)->exists($path)) {
            return null;
        }

        if ($disk === 's3') {
            return Storage::disk('s3')->url($path);
        }

        // Local / public disk
        $url = Storage::disk($disk)->url($path);
        $url = str_replace('/storage/app/public', '/storage', $url);

        if (!file_exists(public_path('storage'))) {
            $url = asset('storage/app/public/' . ltrim($path, '/'));
        }

        return $url;

    } catch (\Exception $e) {
        return null;
    }
}

/**
 * Read settings from DB (cached statically per request).
 */
if (!function_exists('company')) {
    function company(?string $key = null, $default = null)
    {
        static $settings;

        if (!$settings) {
            try {
                $settings = DB::table('settings')
                    ->pluck('value', 'key')
                    ->toArray();
            } catch (\Throwable $e) {
                $settings = [];
            }
        }

        if ($key) {
            return $settings[$key] ?? $default;
        }

        return $settings;
    }
}
