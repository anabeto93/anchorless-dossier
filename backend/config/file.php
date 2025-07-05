<?php

return [
    'storage' => [
        'disk' => env('FILE_STORAGE_DISK', 'local'),
        'path' => env('FILE_STORAGE_PATH', 'user/files'),
        'url' => env('FILE_STORAGE_URL', '/files'),
    ],
];
