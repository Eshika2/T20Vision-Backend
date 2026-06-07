<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScorePrediction extends Model
{
    use HasFactory;

    protected $table = 'score_predictions';

    protected $fillable = [
        'user_id',
        'batting_team',
        'bowling_team',
        'venue',
        'current_score',
        'wickets_lost',
        'balls_remaining',
        'last_five',
        'current_run_rate',
        'predicted_final_score',
        'status',
        'message',
    ];
}