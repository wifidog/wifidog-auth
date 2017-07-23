<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client as HttpClient;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * validate a user.
     * Upon successfull login, the client will be redirected to the gateway. http://" . $gw_address . ":" . $gw_port . "/wifidog/auth?token=" . $token
     * Then gateway will request auth_server/auth/?stage=login&token=xxx, auth_server will return text like "Auth: 1".
     * 0 - AUTH_DENIED - User firewall users are deleted and the user removed. Client will be redirected to auth_server/gw_message.php?message=denied
     * 6 - AUTH_VALIDATION_FAILED - User email validation timeout has occured and user/firewall is deleted. Client will be redirected to auth_server/gw_message.php?message=failed_validation
     * 1 - AUTH_ALLOWED - User was valid, add firewall rules if not present. Client will be redirected to auth_server/portal/?gw_id=xxx
     * 5 - AUTH_VALIDATION - Permit user access to email to get validation email under default rules. Client will access /gw_message.php?message=activate
     * -1 - AUTH_ERROR - An error occurred during the validation process. Gateway will show error in this page(gateway/wifidog/auth?token=xxx), no redirect.
     *
     * @example curl 'http://wifidog-auth.lan/auth/?stage=login&ip=192.168.199.123&mac=84:ef:18:ec:db:73&token=thisIsAToken&incoming=0&outgoing=0&gw_id=D4EE073700C2'
     * @link http://dev.wifidog.org/wiki/doc/developer/WiFiDogProtocol_V1#Authserverauthenticationprotocol
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'gw_id' => 'required|string',
            'token' => 'required|string',
            'stage' => 'required|string|in:login,logout,counters',
            'incoming' => 'required_if:stage,counters|integer',
            'outgoing' => 'required_if:stage,counters|integer',
            'ip' => 'ip',
            'mac' => 'string',
        ]);
        $user_status = -1;
        $status = 400;
        if (!$validator->fails()) {
            try {
                $user = JWTAuth::parseToken()->authenticate();
                $user_status = 1;
                $status = 200;
                Log::debug('JWTAuth user ' . var_export($user, true));
            } catch (\Exception $e) {
                $user_status = 0;
                $status = 401;
                Log::error('JWTAuth error ' . $e->getMessage());
            }
        }
        return response('Auth: ' . $user_status, $status)->header('Content-Type', 'text/plain');
    }
}
