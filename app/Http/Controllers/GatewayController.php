<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Lang;
use App\Gateway;

class GatewayController extends Controller
{
    /**
     * Gateway heartbeating (Ping Protocol)
     *
     * @example curl 'http://wifidog-auth.lan/ping?gw_id=001217DA42D2&sys_uptime=742725&sys_memfree=2604&sys_load=0.03&wifidog_uptime=3861'
     * @link http://dev.wifidog.org/wiki/doc/developer/WiFiDogProtocol_V1#GatewayheartbeatingPingProtocol
     *
     * @return \Illuminate\Http\Response
     */
    public function ping(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gw_id' => 'required|string',
            'sys_uptime' => 'required|integer',
            'sys_memfree' => 'required|integer',
            'sys_load' => 'required|numeric',
            'wifidog_uptime' => 'required|integer',
        ]);
        $r = 'Error: params wrong';
        if ($validator->fails()) {
            return response()->txt($r, 400);
        }
        $input = $validator->getData();
        $input['id'] = $input['gw_id'];
        unset($input['gw_id']);
        if (config('wifidog.allow_unknown_gateway')) {
            Gateway::updateOrCreate(['id' => $input['id']], $input);
            $r = 'Pong';
        } else {
            $gw = Gateway::find($input['id']);
            if (!empty($gw)) {
                $gw->update($input);
                $r = 'Pong';
            } else {
                $r = 'Error: not allow unknown gateway';
            }
        }
        return response($r, 200)->header('Content-Type', 'text/plain');
    }
}
