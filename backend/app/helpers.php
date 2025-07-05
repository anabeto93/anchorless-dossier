<?php

if (!function_exists('generateUniqueFileId')) {
    /**
     * Generate a unique file ID for uploaded files
     * 
     * @param string|null $originalName Original filename (optional)
     * @param string $prefix Prefix for the file ID (default: 'file')
     * @param int $length Length of the random component (default: 12)
     * @return string Unique file ID
     */
    function generateUniqueFileId(?string $originalName = null, string $prefix = 'file', int $length = 12): string
    {
        // Generate timestamp component (microseconds for higher uniqueness)
        $timestamp = (string) (microtime(true) * 10000);
        
        // Generate random component
        $random = bin2hex(random_bytes($length / 2));
        
        // Optional: Include sanitized original filename component
        $nameComponent = '';
        if ($originalName) {
            $nameComponent = '_' . \Illuminate\Support\Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            // Limit length to prevent overly long IDs
            $nameComponent = \Illuminate\Support\Str::limit($nameComponent, 20, '');
        }
        
        // Combine components
        $fileId = $prefix . '_' . $timestamp . '_' . $random . $nameComponent;
        
        return $fileId;
    }
}

if (!function_exists('getDefaultFilePath')) {
    /**
     * Get the default file path for a given file ID
     * 
     * @param string $fileId The file ID
     * @return string The default file path
     */
    function getDefaultFilePath(string $fileId): string
    {
        return str_replace('//', '/', config('file.storage.path') . '/' . $fileId);
    }
}

if (!function_exists('constructFileUrl')) {
    /**
     * Construct the file URL from a given file metadata
     * 
     * @param \App\Models\FileMetadata $file The file metadata
     * @return string The file URL
     */
    function constructFileUrl(\App\Models\FileMetadata $file): string
    {
        $disk = $file->disk ?? config('file.storage.disk');
        $path = $file->path ?? config('file.storage.path');
        $url = \Illuminate\Support\Facades\Storage::disk($disk)->url($path);
        return config('app.url') . str_replace('//', '/', '/' . $url);
    }
}

if (!function_exists('generateSignedFilePreviewUrl')) {
    /**
     * Generate a temporary signed URL for file preview.
     *
     * @param string $fileId
     * @return string
     */
    function generateSignedFilePreviewUrl(string $fileId): string
    {
        $expiration = now()->addMinutes(
            config('file.storage.preview_duration', 60)
        );

        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'files.preview',
            $expiration,
            ['file' => $fileId]
        );
    }
}