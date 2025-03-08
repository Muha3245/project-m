<?php

namespace App\Helpers;

class AgoraTokenBuilder
{
    const ROLE_PUBLISHER = 1;
    private static $VERSION = "006";
    
    public static function buildTokenWithUid($appId, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs)
    {
        $token = "";
        
        // Add version
        $token .= self::$VERSION;
        
        // Add app ID
        $token .= $appId;
        
        // Add timestamp
        $timestamp = time();
        $token .= sprintf("%010d", $timestamp);
        
        // Add random salt
        $salt = rand(0, 100000);
        $token .= sprintf("%08x", $salt);
        
        // Add channel name
        $token .= pack("H*", sprintf("%08x", crc32($channelName)));
        
        // Add UID
        $token .= pack("H*", sprintf("%08x", $uid));
        
        // Add role
        $token .= pack("H*", sprintf("%02x", $role));
        
        // Add privilege expired timestamp
        $token .= pack("H*", sprintf("%08x", $privilegeExpiredTs));
        
        // Calculate signature
        $signature = hash_hmac('sha256', $token, $appCertificate, true);
        
        return base64_encode($token . $signature);
    }
} 