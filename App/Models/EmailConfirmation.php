<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailConfirmation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'user_type_id',
        'email_address',
        'otp',
        'attempt_count',
        'attempt_release_time',
        'reference',
        'otp_valid_time',
        'valid_count',
        'otp_release_time',
        'is_active',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'attempt_release_time' => 'datetime',
        'otp_valid_time' => 'datetime',
        'otp_release_time' => 'datetime'
    ];
}
