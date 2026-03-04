<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Version;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Exception;
class CheckAppVersion
{
    protected function logError($url, $error_message)
    {
        Log::error('Error in check app version middleware function', [
            'url' => $url,
            'error' => $error_message
        ]);
        $output['success'] = false;
        $output['data'] = null;
        $output['message'] = "Error log updated";
        return response()->json(['success' => $output['success'],'message' => $output['message'], 'output' => $output['data']], 401);
    }

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
            $build_number = intval($request->header('Build-Number'));
            $app_type = intval($request->header('App-Type'));
            $os_type = intval($request->header('OS-Type'));

            // dd($build_number, $app_type, $os_type);

            $maintain_data = Setting::select('value')->where('id', 1)->where('is_active', 1)->orderBy('id', 'DESC')->first();
            $maintain_mode = isset($maintain_data->value) ? intval($maintain_data->value) : 0;
            if($maintain_mode == 1) {
                $output['success'] = false;
                $output['message'] = "Server in maintain mode.";
                $output['data'] = ['maintain_mode' => 1];
                return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 503);

            } else {
                if ($build_number > 0 /*&& $app_type > 0*/) {
                    if($os_type > 0) {
                        $version_data = Version::where('build_number', '>', $build_number)
                                            ->where('app_type', $app_type)
                                            ->where('is_Active', 1)
                                            ->where(function ($query) use ($os_type) {
                                                $query->where('os_type', 0)
                                                      ->orWhere('os_type', $os_type);
                                            })
                                            ->orderBy('id', 'DESC')->first();
                    } else {
                        $version_data = Version::where('build_number', '>', $build_number)
                                            ->where('app_type', $app_type)
                                            ->where('is_Active', 1)
                                            ->where('os_type', 0)
                                            ->orderBy('id', 'DESC')->first();
                    }
                    // dd($version_data);
                    
                    if (isset($version_data->id)) {
                        $output['success'] = false;
                        $output['message'] = "You have a critical app update.";
                        $output['data'] = ['has_update' => 1];
                        return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 403);
                    }
                }
            }
        } catch (Exception $e) {
            $url = $request->url();
            $error_message = $e->getMessage();
            $this->logError($url, $error_message);
            $output['success'] = false;
            $output['message'] = "Something went wrong, please try again: " . $e->getMessage();
            $output['data'] = null;
            return response()->json(['success' => $output['success'], 'message' => $output['message'], 'output' => $output['data']], 500);
        }
        return $next($request);
    }
}
