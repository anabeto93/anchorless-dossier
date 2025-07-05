<?php

return [
    'storage' => [
        'path' => env('FILE_STORAGE_PATH', storage_path('app/files')),
        'url' => env('FILE_STORAGE_URL', '/files'),
    ],
];
