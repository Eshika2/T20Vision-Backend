<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Log facade at the top

class SettingController extends Controller
{
    private $settingRepository;

    public function __construct(SettingRepositoryInterface $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }
    protected function logError($url, $error_message)
    {
        Log::error('Error in user controller function', [
            'url' => $url,
            'error' => $error_message
        ]);
    }

    public function winPrediction (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'batting_team' => 'required|string|max:100',
                'bowling_team' => 'required|string|max:100|different:batting_team',
                'venue' => 'required|string|max:255',
                'target' => 'required|numeric|min:1',
                'score' => 'required|numeric|min:0',
                'overs_completed' => 'required|numeric|min:0|max:20',
                'wickets_out' => 'required|integer|min:0|max:10',
            ]);

            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $data['user_id'] = Auth::user()->id;

                $out_data = $this->settingRepository->winPrediction($data);

                $output['success'] = $out_data['success'];
                $output['message'] = $out_data['message'];
                $output['data'] = $out_data['data'];
                $status = $out_data['status'];
            }
        } catch (\Exception $e) {
            $url = $request->url();
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $status = 500;
        }

        return response()->json([
            'success' => $output['success'],
            'message' => $output['message'],
            'output' => $output['data']
        ], $status);
    }
    public function scorePrediction (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'batting_team' => 'required|string|max:100',
                'bowling_team' => 'required|string|max:100|different:batting_team',
                'venue' => 'required|string|max:255',
                'current_score' => 'required|integer|min:0',
                'wickets_lost' => 'required|integer|min:0|max:10',
                'balls_remaining' => 'required|integer|min:0|max:120',
                'last_five' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $data['user_id'] = Auth::user()->id;

                $out_data = $this->settingRepository->scorePrediction($data);

                $output['success'] = $out_data['success'];
                $output['message'] = $out_data['message'];
                $output['data'] = $out_data['data'];
                $status = $out_data['status'];
            }
        } catch (\Exception $e) {
            $url = $request->url();
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $status = 500;
        }

        return response()->json([
            'success' => $output['success'],
            'message' => $output['message'],
            'output' => $output['data']
        ], $status);
    }
    public function teamRecommendation (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'my_team' => 'required|string|max:100',
                'opponent_team' => 'required|string|max:100|different:my_team',
                'venue' => 'required|string|max:255',
                'batters' => 'nullable|integer|min:0|max:11',
                'bowlers' => 'nullable|integer|min:0|max:11',
                'allrounders' => 'nullable|integer|min:0|max:11',
                'start_year' => 'nullable|integer|digits:4',
                'end_year' => 'nullable|integer|digits:4',
            ]);

            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $data['user_id'] = Auth::user()->id;

                $out_data = $this->settingRepository->teamRecommendation($data);

                $output['success'] = $out_data['success'];
                $output['message'] = $out_data['message'];
                $output['data'] = $out_data['data'];
                $status = $out_data['status'];
            }
        } catch (\Exception $e) {
            $url = $request->url();
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $status = 500;
        }

        return response()->json([
            'success' => $output['success'],
            'message' => $output['message'],
            'output' => $output['data']
        ], $status);
    }
    public function predictionHistory(Request $request) {
        try {
            $data = json_decode($request->getContent(), true);
            $data['url'] = $request->url();
            $data['user_id'] = Auth::user()->id;

            $out_data = $this->settingRepository->predictionHistory($data);

            $output['success'] = $out_data['success'];
            $output['message'] = $out_data['message'];
            $output['data'] = $out_data['data'];
            $status = $out_data['status'];
        } catch (\Exception $e) {
            $url = $request->url();
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);

            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $status = 500;
        }

        return response()->json([
            'success' => $output['success'],
            'message' => $output['message'],
            'output' => $output['data']
        ], $status);
    }
}