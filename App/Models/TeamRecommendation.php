<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamRecommendation extends Model
{
    use HasFactory;

    protected $table = 'team_recommendations';

    protected $fillable = [
        'user_id',
        'my_team',
        'opponent_team',
        'venue',
        'batters',
        'bowlers',
        'allrounders',
        'start_year',
        'end_year',
        'available_players',
        'overall_rows',
        'opponent_rows',
        'venue_rows',
        'exact_rows',
        'status',
        'message',
    ];

    public function players()
    {
        return $this->hasMany(TeamRecommendationPlayer::class, 'team_recommendation_id');
    }
}