<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'build_number',
        'app_version',
        'os_type',
        'update_type',
        'is_active',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
}

