<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \RtcTokenBuilder\RtcTokenBuilder;

class AgoraController extends Controller
{
    public function getToken(Request $request)
    {
        try {
            $channelName = $request->query('channel');
            if (!$channelName) {
                return response()->json(['error' => 'Channel name is required'], 400);
            }

            $appId = "8e7c103dab4d44e0995a874e3f8a266a";
            $appCertificate = '3c9fddd9884e447ea6a81de5607f41df';
            $uid = 0;
            $role = 2;
            $expireTimeInSeconds = 3600;
            $currentTimestamp = now()->timestamp;
            $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

            $token = \RtcTokenBuilder::buildTokenWithUid($appId, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs);

            return response()->json(['token' => $token]);
        } catch (\Exception $e) {
            \Log::error('Agora Token Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate token'], 500);
        }
    }
}
