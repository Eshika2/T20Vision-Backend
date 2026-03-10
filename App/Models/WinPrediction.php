<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WinPrediction extends Model
{
    use HasFactory;

    protected $table = 'win_predictions';

    protected $fillable = [
        'user_id',
        'batting_team',
        'bowling_team',
        'venue',
        'target',
        'score',
        'overs_completed',
        'wickets_out',
        'batting_win_probability',
        'bowling_win_probability',
        'status',
        'message',
    ];
}