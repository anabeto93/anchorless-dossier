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
