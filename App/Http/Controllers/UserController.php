<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Log facade at the top

class UserController extends Controller
{
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    protected function logError($url, $error_message)
    {
        Log::error('Error in user controller function', [
            'url' => $url,
            'error' => $error_message
        ]);
    }

    public function userRegister(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'user_type_id' => 'nullable|integer|exists:user_types,id',
                'full_name' => 'required|string|min:1|max:255',
                'user_name' => 'nullable|string|min:1|max:255',
                'email_address' => 'required|string|email|min:1|max:255|unique:users',
                'age' => 'required|integer|min:1|max:255',
                'password' => 'required|string|min:1|max:256|confirmed',
            ]);
            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $out_data = $this->userRepository->userRegister($data);

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
        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], $status);
    }
    public function userLogin(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'email_address' => 'required|string|email|min:1|max:255',
                'password' => 'required|string|min:1|max:256',
            ]);
            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $out_data = $this->userRepository->userLogin($data);

                $output['success'] = $out_data['success'];
                $output['message'] = $out_data['message'];
                $output['data'] = $out_data['data'];
                $status = $out_data['status'];
            }
        } catch (\Exception $e) {
            $status = 500;
            $url = $request->url();
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);
            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
        }

        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], $status);
    }
    public function logout() {
        Auth::guard('api')->logout();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }
    public function userData() {
        try {
            $out_data = $this->userRepository->userData();

            $output['success'] = $out_data['success'];
            $output['message'] = $out_data['message'];
            $output['data'] = $out_data['data'];
            $status = $out_data['status'];
        } catch (\Exception $e) {
            $url = "auth/data";
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);
            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $status = 500;
        }

        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], $status);
    }
    public function userValidate(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'email_address' => 'required|string|email|min:1|max:255',
            ]);
            $status = 200;
            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $out_data = $this->userRepository->userValidate($data);

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

        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], $status);
    }
    public function userAll(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'user_type_id' => 'nullable|integer',
                'search' => 'nullable|string|min:1|max:255',
                'is_active' => 'nullable|integer|in:0,1,-1',
            ]);
            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $out_data = $this->userRepository->userAll($data);

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
        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], $status);
    }
    public function userPasswordReset(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'email_address' => 'required|string|email|min:1|max:255',
                'new_password' => 'required|string|min:1|max:256|confirmed',
            ]);
            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $out_data = $this->userRepository->userPasswordReset($data);

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
        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], $status);
    }
    public function generateOTP(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'email_address' => 'required|string|email|min:1|max:255',
            ]);
            $status = 200;
            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $out_data = $this->userRepository->generateOTP($data);

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

        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], $status);
    }
    public function verifyOTP(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                "email_address" => "required|string|email|min:1|max:255",
                "otp" => "required|string|min:6",
                "reference" => "required|string|min:16"
            ]);
            if ($validator->fails()) {
                $output['success'] = false;
                $output['message'] = $validator->errors()->first();
                $output['data'] = null;
                $status = 422;
            } else {
                $data = json_decode($request->getContent(), true);
                $data['url'] = $request->url();
                $out_data = $this->userRepository->verifyOTP($data);

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
        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], $status);
    }
}