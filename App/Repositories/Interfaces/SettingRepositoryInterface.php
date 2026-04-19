<?php

namespace App\Repositories\Interfaces;


interface SettingRepositoryInterface
{
    public function encryptText($data);
    public function decryptText($data);

    public function winPrediction(array $data);
    public function scorePrediction(array $data);
    public function teamRecommendation(array $data);
    public function predictionHistory(array $data);

    public function winTeams(array $data);
    public function winVenues(array $data);
    public function scoreTeams(array $data);
    public function scoreVenues(array $data);
}