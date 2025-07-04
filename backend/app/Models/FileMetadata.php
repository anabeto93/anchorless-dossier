<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileMetadata extends Model
{
    protected $fillable = [
        'file_id',
        'name',
        'size',
        'mime_type',
        'user_id'
    ];
}
