<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamRecommendationPlayer extends Model
{
    use HasFactory;

    protected $table = 'team_recommendation_players';

    protected $fillable = [
        'team_recommendation_id',
        'player_name',
        'role',
        'reward',
    ];

    public function recommendation()
    {
        return $this->belongsTo(TeamRecommendation::class, 'team_recommendation_id');
    }
}