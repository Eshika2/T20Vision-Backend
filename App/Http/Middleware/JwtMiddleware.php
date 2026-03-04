<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Illuminate\Http\Request;
use App\Models\Setting;

class JwtMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $maintain_mode_data = Setting::where('id', 1)->first();
            $maintain_mode = isset($maintain_mode_data->value) ? intval($maintain_mode_data->value) : 0;
            if($maintain_mode == 1) {
                $output['success'] = false;
                $output['data'] = null;
                $output['message'] = "System Under Maintain";
                return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 503);
            } else {
                $user = JWTAuth::parseToken()->authenticate();
                if(isset($user->id)) {
                    $user_id1 = isset($user->id) ? intval($user->id) : 0;
                    // $user_id2 = intval($request->header('USER_ID'));
                    $user_id2 = intval($request->header('USER_ID')) != 0 ? intval($request->header('USER_ID')) : $user_id1;
                    if($user_id1 != $user_id2) {
                        $output['success'] = false;
                        $output['data'] = null;
                        $output['message'] = "User not match";
                        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 403);
                    }
                } else {
                    $output['success'] = false;
                    $output['data'] = null;
                    $output['message'] = "User not exit";
                    return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 401);
                }
            }
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                $output['success'] = false;
                $output['data'] = null;
                $output['message'] = "Token is Invalid";
                return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 401);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                $output['success'] = false;
                $output['data'] = null;
                $output['message'] = "Token is Expired";
                return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 401);
            } else {
                $output['success'] = false;
                $output['data'] = null;
                $output['message'] = "Authorization Token not found";
                return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 401);
            }
        }
        return $next($request);
    }
}
