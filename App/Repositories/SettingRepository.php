<?php

namespace App\Repositories;

use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Support\Facades\Log; // Import Log facade at the top
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\TeamRecommendation;
use App\Models\TeamRecommendationPlayer;
use App\Models\WinPrediction;
use App\Models\ScorePrediction;

class SettingRepository implements SettingRepositoryInterface
{
    protected function logError($url, $error_message)
    {
        Log::error('Error in user repository function', [
            'url' => $url,
            'error' => $error_message
        ]);
    }
    public function encryptText($text) {
        $encrypt_key = env('ENCRYPT_KEY');
        $encrypt_code = env('ENCRYPT_CODE');
        // Encrypt the text
        $encryptedText = openssl_encrypt($text, 'AES-128-CBC', $encrypt_key, OPENSSL_RAW_DATA, $encrypt_code);
        if($encryptedText == '' || $encryptedText == null) {
            return $text;
        }
        // Return the encrypted text in Base64 encoding
        return base64_encode($encryptedText);
    }
    public function decryptText($encryptedText) {
        $encrypt_key = env('ENCRYPT_KEY');
        $encrypt_code = env('ENCRYPT_CODE');
        $text = $encryptedText;
        // Decode the text from Base64
        $encryptedText = base64_decode($encryptedText);
        // Decrypt the text
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $encrypt_key, OPENSSL_RAW_DATA, $encrypt_code);
        if($decryptedText == '' || $decryptedText == null) {
            return $text;
        }
        return $decryptedText;
    }
    function generateRandomString($length, $type)
    {
        if (intval($type) == 1) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } else if (intval($type) == 2) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*+-/?><';
        } else if (intval($type) == 3) {
            $characters = '0123456789';
        } else if (intval($type) == 4) {
            $characters = '0123456789abcdefg';
        } else {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    public function winPrediction (array $data) {
        try {
            $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
            $batting_team = isset($data['batting_team']) ? trim($data['batting_team']) : null;
            $bowling_team = isset($data['bowling_team']) ? trim($data['bowling_team']) : null;
            $venue = isset($data['venue']) ? trim($data['venue']) : null;
            $target = isset($data['target']) ? floatval($data['target']) : 0;
            $score = isset($data['score']) ? floatval($data['score']) : 0;
            $overs_completed = isset($data['overs_completed']) ? floatval($data['overs_completed']) : 0;
            $wickets_out = isset($data['wickets_out']) ? intval($data['wickets_out']) : 0;

            $response = Http::timeout(60)->post(env('PYTHON_ML_API_URL') . '/api/win/predict', [
                'batting_team' => $batting_team,
                'bowling_team' => $bowling_team,
                'venue' => $venue,
                'target' => $target,
                'score' => $score,
                'overs_completed' => $overs_completed,
                'wickets_out' => $wickets_out,
            ]);

            $result = $response->json();

            if (!$response->successful()) {
                WinPrediction::create([
                    'user_id' => $user_id,
                    'batting_team' => $batting_team,
                    'bowling_team' => $bowling_team,
                    'venue' => $venue,
                    'target' => $target,
                    'score' => $score,
                    'overs_completed' => $overs_completed,
                    'wickets_out' => $wickets_out,
                    'status' => 0,
                    'message' => isset($result['error']) ? $result['error'] : 'Python API error',
                ]);

                $output['success'] = false;
                $output['message'] = isset($result['error']) ? $result['error'] : 'Prediction failed';
                $output['data'] = null;
                $output['status'] = 400;
            } else {
                $prediction = WinPrediction::create([
                    'user_id' => $user_id,
                    'batting_team' => $batting_team,
                    'bowling_team' => $bowling_team,
                    'venue' => $venue,
                    'target' => $target,
                    'score' => $score,
                    'overs_completed' => $overs_completed,
                    'wickets_out' => $wickets_out,
                    'batting_win_probability' => isset($result['batting_win_probability']) ? $result['batting_win_probability'] : null,
                    'bowling_win_probability' => isset($result['bowling_win_probability']) ? $result['bowling_win_probability'] : null,
                    'status' => 1,
                    'message' => 'Success',
                ]);

                $output['success'] = true;
                $output['message'] = "Success";
                $output['data'] = [
                    'id' => $prediction->id,
                    'batting_team' => $batting_team,
                    'bowling_team' => $bowling_team,
                    'venue' => $venue,
                    'target' => $target,
                    'score' => $score,
                    'overs_completed' => $overs_completed,
                    'wickets_out' => $wickets_out,
                    'batting_win_probability' => $prediction->batting_win_probability,
                    'bowling_win_probability' => $prediction->bowling_win_probability,
                ];
                $output['status'] = 200;
            }
        } catch (\Exception $e) {
            $url = isset($data['url']) ? $data['url'] : null;
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }

        return $output;
    }
    public function scorePrediction (array $data) {
        try {
            $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
            $batting_team = isset($data['batting_team']) ? trim($data['batting_team']) : null;
            $bowling_team = isset($data['bowling_team']) ? trim($data['bowling_team']) : null;
            $venue = isset($data['venue']) ? trim($data['venue']) : null;
            $current_score = isset($data['current_score']) ? intval($data['current_score']) : 0;
            $wickets_lost = isset($data['wickets_lost']) ? intval($data['wickets_lost']) : 0;
            $balls_remaining = isset($data['balls_remaining']) ? intval($data['balls_remaining']) : 0;
            $last_five = isset($data['last_five']) ? intval($data['last_five']) : 0;

            $response = Http::timeout(60)->post(env('PYTHON_ML_API_URL') . '/api/score/predict', [
                'batting_team' => $batting_team,
                'bowling_team' => $bowling_team,
                'venue' => $venue,
                'current_score' => $current_score,
                'wickets_lost' => $wickets_lost,
                'balls_remaining' => $balls_remaining,
                'last_five' => $last_five,
            ]);

            $result = $response->json();

            if (!$response->successful()) {
                ScorePrediction::create([
                    'user_id' => $user_id,
                    'batting_team' => $batting_team,
                    'bowling_team' => $bowling_team,
                    'venue' => $venue,
                    'current_score' => $current_score,
                    'wickets_lost' => $wickets_lost,
                    'balls_remaining' => $balls_remaining,
                    'last_five' => $last_five,
                    'status' => 0,
                    'message' => isset($result['error']) ? $result['error'] : 'Python API error',
                ]);

                $output['success'] = false;
                $output['message'] = isset($result['error']) ? $result['error'] : 'Prediction failed';
                $output['data'] = null;
                $output['status'] = 400;
            } else {
                $prediction = ScorePrediction::create([
                    'user_id' => $user_id,
                    'batting_team' => $batting_team,
                    'bowling_team' => $bowling_team,
                    'venue' => $venue,
                    'current_score' => $current_score,
                    'wickets_lost' => $wickets_lost,
                    'balls_remaining' => $balls_remaining,
                    'last_five' => $last_five,
                    'current_run_rate' => isset($result['current_run_rate']) ? $result['current_run_rate'] : null,
                    'predicted_final_score' => isset($result['predicted_final_score']) ? $result['predicted_final_score'] : null,
                    'status' => 1,
                    'message' => 'Success',
                ]);

                $output['success'] = true;
                $output['message'] = "Success";
                $output['data'] = [
                    'id' => $prediction->id,
                    'batting_team' => $prediction->batting_team,
                    'bowling_team' => $prediction->bowling_team,
                    'venue' => $prediction->venue,
                    'current_score' => $prediction->current_score,
                    'wickets_lost' => $prediction->wickets_lost,
                    'balls_remaining' => $prediction->balls_remaining,
                    'last_five' => $prediction->last_five,
                    'current_run_rate' => $prediction->current_run_rate,
                    'predicted_final_score' => $prediction->predicted_final_score,
                ];
                $output['status'] = 200;
            }
        } catch (\Exception $e) {
            $url = isset($data['url']) ? $data['url'] : null;
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }

        return $output;
    }
    public function teamRecommendation (array $data) {
        try {
            $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
            $my_team = isset($data['my_team']) ? trim($data['my_team']) : null;
            $opponent_team = isset($data['opponent_team']) ? trim($data['opponent_team']) : null;
            $venue = isset($data['venue']) ? trim($data['venue']) : null;
            $batters = isset($data['batters']) ? intval($data['batters']) : 5;
            $bowlers = isset($data['bowlers']) ? intval($data['bowlers']) : 3;
            $allrounders = isset($data['allrounders']) ? intval($data['allrounders']) : 3;
            $start_year = isset($data['start_year']) ? intval($data['start_year']) : 2023;
            $end_year = isset($data['end_year']) ? intval($data['end_year']) : 2026;

            if ($batters + $bowlers + $allrounders != 11) {
                $output['success'] = false;
                $output['message'] = "Total players should be 11.";
                $output['data'] = null;
                $output['status'] = 400;
                return $output;
            }

            $response = Http::timeout(240)->post(env('PYTHON_ML_API_URL') . '/api/team/recommend', [
                'my_team' => $my_team,
                'opponent_team' => $opponent_team,
                'venue' => $venue,
                'batters' => $batters,
                'bowlers' => $bowlers,
                'allrounders' => $allrounders,
                'start_year' => $start_year,
                'end_year' => $end_year,
            ]);

            $result = $response->json();

            if (!$response->successful() || isset($result['error'])) {
                $recommendation = TeamRecommendation::create([
                    'user_id' => $user_id,
                    'my_team' => $my_team,
                    'opponent_team' => $opponent_team,
                    'venue' => $venue,
                    'batters' => $batters,
                    'bowlers' => $bowlers,
                    'allrounders' => $allrounders,
                    'start_year' => $start_year,
                    'end_year' => $end_year,
                    'overall_rows' => isset($result['context_info']['overall_rows']) ? $result['context_info']['overall_rows'] : null,
                    'opponent_rows' => isset($result['context_info']['opponent_rows']) ? $result['context_info']['opponent_rows'] : null,
                    'venue_rows' => isset($result['context_info']['venue_rows']) ? $result['context_info']['venue_rows'] : null,
                    'exact_rows' => isset($result['context_info']['exact_rows']) ? $result['context_info']['exact_rows'] : null,
                    'status' => 0,
                    'message' => isset($result['error']) ? $result['error'] : 'Recommendation failed',
                ]);

                $output['success'] = false;
                $output['message'] = isset($result['error']) ? $result['error'] : 'Recommendation failed';
                $output['data'] = null;
                $output['status'] = 400;
            } else {
                DB::beginTransaction();

                $recommendation = TeamRecommendation::create([
                    'user_id' => $user_id,
                    'my_team' => $my_team,
                    'opponent_team' => $opponent_team,
                    'venue' => $venue,
                    'batters' => $batters,
                    'bowlers' => $bowlers,
                    'allrounders' => $allrounders,
                    'start_year' => $start_year,
                    'end_year' => $end_year,
                    'available_players' => isset($result['available_players']) ? $result['available_players'] : null,
                    'overall_rows' => isset($result['context_info']['overall_rows']) ? $result['context_info']['overall_rows'] : null,
                    'opponent_rows' => isset($result['context_info']['opponent_rows']) ? $result['context_info']['opponent_rows'] : null,
                    'venue_rows' => isset($result['context_info']['venue_rows']) ? $result['context_info']['venue_rows'] : null,
                    'exact_rows' => isset($result['context_info']['exact_rows']) ? $result['context_info']['exact_rows'] : null,
                    'status' => 1,
                    'message' => 'Success',
                ]);

                $players_output = [];

                if (isset($result['recommended_team']) && is_array($result['recommended_team'])) {
                    foreach ($result['recommended_team'] as $player) {
                        $new_player = TeamRecommendationPlayer::create([
                            'team_recommendation_id' => $recommendation->id,
                            'player_name' => isset($player['player']) ? $player['player'] : null,
                            'role' => isset($player['role']) ? $player['role'] : null,
                            'reward' => isset($player['reward']) ? $player['reward'] : null,
                        ]);

                        $players_output[] = [
                            'player' => $new_player->player_name,
                            'role' => $new_player->role,
                            'reward' => $new_player->reward,
                        ];
                    }
                }

                DB::commit();

                $output['success'] = true;
                $output['message'] = "Success";
                $output['data'] = [
                    'id' => $recommendation->id,
                    'my_team' => $recommendation->my_team,
                    'opponent_team' => $recommendation->opponent_team,
                    'venue' => $recommendation->venue,
                    'batters' => $recommendation->batters,
                    'bowlers' => $recommendation->bowlers,
                    'allrounders' => $recommendation->allrounders,
                    'start_year' => $recommendation->start_year,
                    'end_year' => $recommendation->end_year,
                    'available_players' => $recommendation->available_players,
                    'context_info' => [
                        'overall_rows' => $recommendation->overall_rows,
                        'opponent_rows' => $recommendation->opponent_rows,
                        'venue_rows' => $recommendation->venue_rows,
                        'exact_rows' => $recommendation->exact_rows,
                    ],
                    'recommended_team' => $players_output,
                ];
                $output['status'] = 200;
            }
        } catch (\Exception $e) {
            DB::rollBack();

            $url = isset($data['url']) ? $data['url'] : null;
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }

        return $output;
    }
    public function predictionHistory(array $data) {
        try {
            $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;

            $win_predictions = WinPrediction::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => intval($item->id),
                        'type' => 'win',
                        'batting_team' => $item->batting_team,
                        'bowling_team' => $item->bowling_team,
                        'venue' => $item->venue,
                        'target' => $item->target,
                        'score' => $item->score,
                        'overs_completed' => $item->overs_completed,
                        'wickets_out' => $item->wickets_out,
                        'batting_win_probability' => $item->batting_win_probability,
                        'bowling_win_probability' => $item->bowling_win_probability,
                        'status' => intval($item->status),
                        'message' => $item->message,
                        'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                    ];
                })->values();

            $score_predictions = ScorePrediction::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => intval($item->id),
                        'type' => 'score',
                        'batting_team' => $item->batting_team,
                        'bowling_team' => $item->bowling_team,
                        'venue' => $item->venue,
                        'current_score' => intval($item->current_score),
                        'wickets_lost' => intval($item->wickets_lost),
                        'balls_remaining' => intval($item->balls_remaining),
                        'last_five' => intval($item->last_five),
                        'current_run_rate' => $item->current_run_rate,
                        'predicted_final_score' => $item->predicted_final_score,
                        'status' => intval($item->status),
                        'message' => $item->message,
                        'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                    ];
                })->values();

            $team_recommendations = TeamRecommendation::with('players')
                ->where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => intval($item->id),
                        'type' => 'team',
                        'my_team' => $item->my_team,
                        'opponent_team' => $item->opponent_team,
                        'venue' => $item->venue,
                        'batters' => intval($item->batters),
                        'bowlers' => intval($item->bowlers),
                        'allrounders' => intval($item->allrounders),
                        'start_year' => intval($item->start_year),
                        'end_year' => intval($item->end_year),
                        'available_players' => $item->available_players,
                        'context_info' => [
                            'overall_rows' => $item->overall_rows,
                            'opponent_rows' => $item->opponent_rows,
                            'venue_rows' => $item->venue_rows,
                            'exact_rows' => $item->exact_rows,
                        ],
                        'recommended_team' => $item->players->map(function ($player) {
                            return [
                                'player' => $player->player_name,
                                'role' => $player->role,
                                'reward' => $player->reward,
                            ];
                        })->values(),
                        'status' => intval($item->status),
                        'message' => $item->message,
                        'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                    ];
                })->values();

            $output['success'] = true;
            $output['message'] = "Success";
            $output['data'] = [
                'win_predictions' => $win_predictions,
                'score_predictions' => $score_predictions,
                'team_recommendations' => $team_recommendations,
                'counts' => [
                    'win_predictions' => $win_predictions->count(),
                    'score_predictions' => $score_predictions->count(),
                    'team_recommendations' => $team_recommendations->count(),
                ]
            ];
            $output['status'] = 200;
            
        } catch (\Exception $e) {
            $url = isset($data['url']) ? $data['url'] : null;
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);
            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }

        return $output;
    }
    public function winTeams(array $data) {
        try {
            $response = Http::timeout(60)->get(env('PYTHON_ML_API_URL') . '/api/win/teams');
            $result = $response->json();

            if (!$response->successful()) {
                $output['success'] = false;
                $output['message'] = isset($result['error']) ? $result['error'] : 'Failed to fetch win teams';
                $output['data'] = null;
                $output['status'] = 400;
            } else {
                $output['success'] = true;
                $output['message'] = "Success";
                $output['data'] = $result;
                $output['status'] = 200;
            }
        } catch (\Exception $e) {
            $url = isset($data['url']) ? $data['url'] : null;
            $this->logError($url, $e->getMessage());

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }

        return $output;
    }
    public function winVenues(array $data) {
        try {
            $response = Http::timeout(60)->post(env('PYTHON_ML_API_URL') . '/api/win/venues', [
                'team1' => isset($data['team1']) ? trim($data['team1']) : null,
                'team2' => isset($data['team2']) ? trim($data['team2']) : null,
                'start_year' => isset($data['start_year']) ? intval($data['start_year']) : null,
                'end_year' => isset($data['end_year']) ? intval($data['end_year']) : null,
            ]);

            $result = $response->json();

            if (!$response->successful()) {
                $output['success'] = false;
                $output['message'] = isset($result['error']) ? $result['error'] : 'Failed to fetch win venues';
                $output['data'] = null;
                $output['status'] = 400;
            } else {
                $output['success'] = true;
                $output['message'] = "Success";
                $output['data'] = $result;
                $output['status'] = 200;
            }
        } catch (\Exception $e) {
            $url = isset($data['url']) ? $data['url'] : null;
            $this->logError($url, $e->getMessage());

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }

        return $output;
    }
    public function scoreTeams(array $data) {
        try {
            $response = Http::timeout(60)->get(env('PYTHON_ML_API_URL') . '/api/score/teams');
            $result = $response->json();

            if (!$response->successful()) {
                $output['success'] = false;
                $output['message'] = isset($result['error']) ? $result['error'] : 'Failed to fetch score teams';
                $output['data'] = null;
                $output['status'] = 400;
            } else {
                $output['success'] = true;
                $output['message'] = "Success";
                $output['data'] = $result;
                $output['status'] = 200;
            }
        } catch (\Exception $e) {
            $url = isset($data['url']) ? $data['url'] : null;
            $this->logError($url, $e->getMessage());

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }

        return $output;
    }
    public function scoreVenues(array $data) {
        try {
            $response = Http::timeout(60)->post(env('PYTHON_ML_API_URL') . '/api/score/venues', [
                'team1' => isset($data['team1']) ? trim($data['team1']) : null,
                'team2' => isset($data['team2']) ? trim($data['team2']) : null,
                'start_year' => isset($data['start_year']) ? intval($data['start_year']) : null,
                'end_year' => isset($data['end_year']) ? intval($data['end_year']) : null,
            ]);

            $result = $response->json();

            if (!$response->successful()) {
                $output['success'] = false;
                $output['message'] = isset($result['error']) ? $result['error'] : 'Failed to fetch score venues';
                $output['data'] = null;
                $output['status'] = 400;
            } else {
                $output['success'] = true;
                $output['message'] = "Success";
                $output['data'] = $result;
                $output['status'] = 200;
            }
        } catch (\Exception $e) {
            $url = isset($data['url']) ? $data['url'] : null;
            $this->logError($url, $e->getMessage());

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }

        return $output;
    }
    
}