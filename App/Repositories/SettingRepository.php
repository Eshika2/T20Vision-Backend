<?php

namespace App\Repositories;

use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Support\Facades\Log; // Import Log facade at the top
use Illuminate\Support\Facades\Http;
use App\Models\WinPrediction;

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
    public function winPrediction(array $data) {
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
}