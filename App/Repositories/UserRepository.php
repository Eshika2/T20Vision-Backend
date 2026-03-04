<?php

namespace App\Repositories;

use App\Models\EmailConfirmation;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Log facade at the top
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class UserRepository implements UserRepositoryInterface
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

    public function userRegister(array $data) {
        try {
            $full_name = isset($data['full_name']) ? trim($data['full_name']) : null;
            $user_name = isset($data['user_name']) ? trim($data['user_name']) : null;
            $user_type_id = isset($data['user_type_id']) ? intval($data['user_type_id']) : 2;
            $email_address = isset($data['email_address']) ? trim($data['email_address']) : null;
            $age = isset($data['age']) ? intval($data['age']) : 0;
            $password = isset($data['password']) ? $data['password'] : null;
            $push_id = isset($data['push_id']) ? $data['push_id'] : null;
            $os_type = isset($data['os_type']) ? intval($data['os_type']) : 0;

            $is_email_exist = User::where('email_address', $email_address)->where('is_active', 1)->first();

            if (isset($is_email_exist->id)) {
                $output['success'] = false;
                $output['message'] = "The email address you've entered is already associated with an existing account!";
                $output['data'] = null;
                $output['status'] = 409;
            } else {
                if ($user_name == null || $user_name == "") {
                    $firstName = explode(" ", trim($full_name))[0];
                    $randomNumber = $this->generateRandomString(4, 3);
                    $user_name = strtolower($firstName . $randomNumber);
                } else {
                    if ($user_type_id != 1) {
                        $user_type_id = 2;
                    }
                    if($password == null || $password == "") {
                        $password = $email_address;
                    }

                    $date_time = Carbon::now();

                    $new_user = User::create([
                        'user_type_id' => $user_type_id,
                        'full_name' => ucwords(strtolower($full_name)),
                        'user_name' => $user_name,
                        'email_address' => $email_address,
                        'age' => $age,
                        'password' => Hash::make($password),
                        'normal_password' => Hash::make($password),
                        'social_password' => Hash::make($email_address),
                        'push_id' => $push_id,
                        'os_type' => $os_type,
                        'is_active' => 1,
                        'created_at' => $date_time,
                        'updated_at' => $date_time
                    ]);

                    $token = JWTAuth::fromUser($new_user);

                    $output['success'] = true;
                    $output['message'] = "Success";
                    $output['data']['user_id'] = isset($new_user->id) ? intval($new_user->id) : 0;
                    $output['data']['token'] = $token;
                    $output['status'] = 201;
                }
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
    public function userLogin(array $data) {
        try {
            $email_address = isset($data['email_address']) ? trim($data['email_address']) : null;
            $password = isset($data['password']) ? $data['password'] : null;
            $push_id = isset($data['push_id']) ? $data['push_id'] : null;
            $os_type = isset($data['os_type']) ? intval($data['os_type']) : 0;

            $user = User::where('email_address', $email_address)
                        ->where('is_active', 1)
                        ->first();

            if (!isset($user->id)) {
                    $output['success'] = false;
                    $output['message'] = "Account does not exist.";
                    $output['data'] = null;
                    $output['status'] = 404;
            } else {
                $credentials['email_address'] = $email_address;
                $credentials['password'] = $password;

                $password = $user->normal_password;
                $user->password = $password;
                $user->save();

                if (!$token = JWTAuth::attempt($credentials)) {
                    $output['success'] = false;
                    $output['message'] = "Invalid credentials. Please check & try again.";
                    $output['data'] = null;
                    $output['status'] = 401;
                } else {
                    $output['success'] = true;
                    $output['message'] = "User sign in success.";
                    $output['data']['user_id'] = isset($user->id) ? intval($user->id) : 0;
                    $output['data']['token'] = $token;
                    $output['status'] = 200;

                    $user->updated_at = Carbon::now();
                    $user->push_id = $push_id;
                    $user->os_type = $os_type;
                    $user->save();
                }
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
    public function userValidate(array $data) {
        try {
            $email_address = isset($data['email_address']) ? trim($data['email_address']) : null;

            $user = User::where('email_address', $email_address)
                        ->where('is_active', 1)
                        ->first();

            $output['success'] = true;
            $output['message'] = "User verification success.";

            if (!isset($user->id)) {
                $output['data']['has_user_id'] = 0;
            } else {
                $output['data']['has_user_id'] = 1;
                $output['data']['user_id'] = isset($user->id) ? intval($user->id) : 0;
            }
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
    public function userData() {
        try {
            $user = Auth::user();
            if (isset($user->id)) {
                $output['success'] = true;
                $output['message'] = "Success";
                $output['data']['user_id'] = isset($user->id) ? intval($user->id) : 0;
                $output['data']['user_type_id'] = isset($user->user_type_id) ? intval($user->user_type_id) : 0;
                $output['data']['full_name'] = isset($user->full_name) ? $user->full_name : null;
                $output['data']['user_name'] = isset($user->user_name) ? $user->user_name : null;
                $output['data']['email_address'] = isset($user->email_address) ? $user->email_address : null;
                $output['data']['password'] = isset($user->password) ? $user->password : null;
                $output['data']['os_type'] = isset($user->os_type) ? intval($user->os_type) : 0;
                $output['data']['push_id'] = isset($user->push_id) ? $user->push_id : null;
                $output['data']['is_otp_verified'] = isset($user->is_otp_verified) ? intval($user->is_otp_verified) : 0;
                $output['status'] = 200;
            } else {
                $output['success'] = false;
                $output['message'] = "User does not exist.";
                $output['data'] = null;
                $output['status'] = 404;
            }
        } catch (\Exception $e) {
            $url = "auth/user";
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);
            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            $output['status'] = 500;
        }
        return $output;
    }
    public function userAll(array $data) {
        try {
            $user_type_id = isset($data['user_type_id']) ? intval($data['user_type_id']) : 0;
            $search = isset($data['search']) ? $data['search'] : null;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 0;
            $page_number = isset($data['page_number']) ? intval($data['page_number']) : 0;
            $per_page = isset($data['per_page']) ? intval($data['per_page']) : 10;

            $query = User::query();

            if ($user_type_id != 0) {
                $query->where('user_type_id', $user_type_id);
            }
            if ($is_active != 0) {
                if ($is_active == 1) {
                    $query->where('is_active', 1);
                } else {
                    $query->where('is_active', 0);
                }
            }
            if(!empty($search)) {
                $query->where(function($q) use ($search) {
                            $q->where('full_name', 'LIKE', '%' . $search . '%')
                            ->orWhere('user_name', 'LIKE', '%' . $search . '%')
                            ->orWhere('email_address', 'LIKE', '%' . $search . '%');
                        });
            }

            $users = $query->orderBy('id', 'DESC')->paginate($per_page, ['*'], 'page', $page_number);

            if ($users->isEmpty()) {
                $output['success'] = false;
                $output['message'] = "User does not exist.";
                $output['data'] = null;
                $output['status'] = 401;
            } else {
                $output_data = [];
                foreach ($users as $user) {
                    $output_data[] = [
                        'id' => isset($user->id) ? intval($user->id) : 0,
                        'user_type_id' => isset($user->user_type_id) ? intval($user->user_type_id) : 0,
                        'full_name' => isset($user->full_name) ? $user->full_name : null,
                        'user_name' => isset($user->user_name) ? $user->user_name : null,
                        'email_address' => isset($user->email_address) ? $user->email_address : null,
                        'is_active' => isset($user->is_active) ? intval($user->is_active) : 0
                    ];
                }

                $output['success'] = true;
                $output['message'] = "Success";
                $output['data'] = $output_data;
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
    public function userPasswordReset(array $data) {
        try {
            $email_address = isset($data['email_address']) ? trim($data['email_address']) : null;
            $password = isset($data['new_password']) ? $data['new_password'] : null;

            $user = User::where('email_address', $email_address)
                        ->where('is_active', 1)
                        ->first();
            if (!isset($user->id)) {
                $output['success'] = false;
                $output['message'] = "User does not exist.";
                $output['data'] = null;
                $output['status'] = 401;
            } else {
                if (Hash::check($password, $user->normal_password)) {
                    $output['success'] = false;
                    $output['message'] = "Password cannot be same as old password.";
                    $output['data'] = null;
                    $output['status'] = 401;
                } else {
                    $user->password = Hash::make($password);
                    $user->normal_password = Hash::make($password);
                    $user->save();

                    $output['success'] = true;
                    $output['message'] = "Password reset successfully.";
                    $output['data'] = null;
                    $output['status'] = 200;
                }
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
    public function generateOTP($data) {
        try {
            $email_address = isset($data['email_address']) ? trim($data['email_address']) : null;
            $should_generate = isset($data['should_generate']) ? intval($data['should_generate']) : 1;
            $date_time = Carbon::now();
            $otp_release_time_data = Setting::where('id', 2)->first();
            $release_time = isset($otp_release_time_data->value) ? intval($otp_release_time_data->value) * 60 : 2 * 60; // 2 minutes
            $otp_max_attempt_data = Setting::where('id', 3)->first();
            $max_attempts = isset($otp_max_attempt_data->value) ? intval($otp_max_attempt_data->value) : 5;

            if (!$email_address) {
                $output['success'] = false;
                $output['message'] = "Email address is required.";
                $output['data'] = null;
                $output['status'] = 400;
            } else {
                $email_data = EmailConfirmation::where('email_address', $email_address)
                                ->where('is_active', 1)
                                ->first();

                $attempt_count = $max_attempts;
                $reference = $this->generateRandomString(16, 4);
                $release_date_time = $date_time->copy()->addSeconds($release_time);

                if (isset($email_data->id)) {
                    $last_release_time = Carbon::parse($email_data->attempt_release_time);
                    $attempt_count = $email_data->attempt_count;
                    //$last_updated = Carbon::parse($email_data->updated_at);

                    // Block if attempts exhausted and less than 24 hours passed
                    $otp_hold_hour_data = Setting::where('id', 4)->first();
                    $otp_hold_hours = isset($otp_hold_hour_data->value) ? intval($otp_hold_hour_data->value) : 24;
                    if ($attempt_count <= 0 && $date_time->diffInHours($last_release_time) < $otp_hold_hours) {
                        if ($date_time->lt($last_release_time)) {
                            $remaining_seconds = $date_time->diffInSeconds($last_release_time);
                            if($remaining_seconds <= 120) {
                                $output['success'] = true;
                                $output['message'] = 'Remaining OTP valid time.';
                                $output['data'] = [
                                    'reference' => $email_data->reference,
                                    'attempt_count' => $attempt_count,
                                    'attempt_release_time' => $remaining_seconds
                                ];
                                $output['status'] = 200;
                            } else {
                                $output['success'] = false;
                                $output['message'] = 'Too many OTP requests. Try again in '.$otp_hold_hours.' hours.';
                                $output['data'] = null;
                                $output['status'] = 401;
                            }
                        } else {
                            $output['success'] = false;
                            $output['message'] = 'Too many OTP requests. Try again in '.$otp_hold_hours.' hours.';
                            $output['data'] = null;
                            $output['status'] = 401;
                        }
                    }

                    // Reset if more than 24 hours passed
                    if ($date_time->diffInHours($last_release_time) >= $otp_hold_hours) {
                        $attempt_count = $max_attempts;
                    }

                    // Still within cooldown
                    if ($date_time->lt($last_release_time)) {
                        $remaining_seconds = $date_time->diffInSeconds($last_release_time);
                        $output['success'] = true;
                        $output['message'] = 'Remaining OTP valid time.';
                        $output['data'] = [
                            'reference' => $email_data->reference,
                            'attempt_count' => $attempt_count,
                            'attempt_release_time' => $remaining_seconds
                        ];
                        $output['status'] = 200;
                    }

                    // Decrease attempt
                    $attempt_count -= 1;
                    $release_date_time = $date_time->copy()->addSeconds($release_time);
                }

                // Generate or reuse OTP
                if ($should_generate == 1) {
                    $otp = $this->generateRandomString(6, 3);
                } else {
                    $otp = $email_data->otp ?? $this->generateRandomString(6, 3);
                }

                // Save to DB
                EmailConfirmation::updateOrCreate(
                [
                    'email_address' => $email_address
                ],
                [
                    'otp' => $otp,
                    'reference' => $reference,
                    'attempt_release_time' => $release_date_time,
                    'attempt_count' => $attempt_count,
                    'otp_valid_time' => $release_date_time,
                    'valid_count' => $max_attempts,
                    'is_active' => 1,
                    'created_at' => $date_time,
                    'updated_at' => $date_time
                ]);

                Mail::send('emails.otp', ['otp' => $otp], function ($message) use ($email_address) {
                    $message->to($email_address);
                    $message->subject('Your OTP Code - MemoLink');
                });

                $output['success'] = true;
                $output['message'] = 'Validation code sent successfully.';
                $output['data'] = [
                    'reference' => $reference,
                    'attempt_count' => $attempt_count,
                    'attempt_release_time' => $release_time
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
    public function verifyOTP($data) {
        try {
            $email_address = isset($data['email_address']) ? $data['email_address'] : null;
            $otp = isset($data['otp']) ? $data['otp'] : null;
            $reference = isset($data['reference']) ? $data['reference'] : null;

            if (!$email_address || !$otp || !$reference) {
                $output['success'] = false;
                $output['message'] = "Wrong user data. Please check & try again.";
                $output['data'] = null;
                $status = 401;
            } else{
                $email_data = EmailConfirmation::where('reference', $reference)
                                ->where('email_address', $email_address)
                                ->where('is_active', 1)
                                ->orderBy('id', 'DESC')->first();

                if (!$email_data) {
                    $output['success'] = false;
                    $output['message'] = "Identification Failed.";
                    $output['data'] = null;
                    $status = 401;
                } else {
                    $now = Carbon::now();
                    $valid_count = intval($email_data->valid_count ?? 0);
                    $otp_release_time = $email_data->otp_release_time ? Carbon::parse($email_data->otp_release_time) : null;
                    $otp_valid_time = $email_data->otp_valid_time ? Carbon::parse($email_data->otp_valid_time) : null;

                    $expire_minutes = intval(Setting::find(46)->value ?? 15);
                    $attempt_count_def = intval(Setting::find(49)->value ?? 3);
                    $valid_count_def = intval(Setting::find(50)->value ?? 5);
                    $delay_min = 0;
                    $date_time = date('Y-m-d H:i:s');
                    //$valid_delay_min = 0;

                    if ($valid_count > 0 || ($valid_count <= 0 && (!$otp_release_time || $otp_release_time->lte($now)))) {
                        if ($otp_valid_time && $otp_valid_time->gte($now)) {
                            $system_otp = $email_data->otp;
                            
                            if ($system_otp == $otp) {
                                // OTP success
                                $email_data->valid_count = $valid_count_def;
                                $email_data->attempt_count = $attempt_count_def;
                                $email_data->attempt_release_time = $now->format($date_time);
                                $email_data->otp_valid_time = $now->format($date_time); // mark as used
                                $email_data->updated_at = $now->format($date_time);
                                $email_data->save();

                                $output['success'] = true;
                                $output['message'] = "OTP Verification Success";
                                $output['data'] = null;
                                $output['status'] = 200;
                            } else {
                                // OTP failed
                                $email_data->valid_count = max(0, $valid_count - 1);
                                if ($email_data->valid_count == 0) {
                                    $email_data->otp_release_time = $now->copy()->addMinutes($expire_minutes)->format($date_time);
                                }
                                $email_data->updated_at = $now->format($date_time);
                                $email_data->save();

                                $output['success'] = false;
                                $output['message'] = "The code you entered doesn't match. Please try again.";
                                $output['data']['time_error'] = 0;
                                $output['data']['valid_delay_min'] = 0;
                                $output['status'] = 401;
                            }
                        } else {
                            // OTP expired
                            $output['success'] = false;
                            $output['message'] = "Your OTP has expired. Request a new code.";
                            $output['data']['time_error'] = 0;
                            $output['data']['valid_delay_min'] = 0;
                            $output['status'] = 403;
                        }
                    } else {
                        // Delay period
                        if ($otp_release_time && $otp_release_time->gt($now)) {
                            $delay_min = $now->diffInMinutes($otp_release_time);
                        } else {
                            $delay_min = 0;
                        }

                        $output['success'] = false;
                        $output['message'] = "Too many failed attempts. Please try again after {$delay_min} minutes.";
                        $output['data']['time_error'] = intval($delay_min);
                        $output['data']['valid_delay_min'] = intval($delay_min) * 60;
                        $output['status'] = 403;
                    }
                }
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