<?php

namespace GigaAI\Http;

use GigaAI\Core\Logger;

class Http
{
    /**
     * Make a POST request to the end point
     *
     * @param  String $url  Url End point
     * @param  array  $data Data to be send
     *
     * @return Mixed
     */
    public static function post($url, $data = [], $method = 'POST')
    {
        $ch = curl_init();
        
        if ( ! empty($data)) {
            Logger::put($data, 'outcoming');
            $data = http_build_query($data);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //to suppress the curl output
        
        $result = curl_exec($ch);

        curl_close($ch);
        
        if (false !== $result) {
            $result = json_decode($result);
        }

        Logger::put($result, 'response');
        return $result;
    }
    
    public static function get($url)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $data = curl_exec($ch);
        curl_close($ch);
        
        Logger::put($url, 'outcoming');
        Logger::put($data, 'response');
        
        return $data;
    }
    
    public static function delete($url, $data = [])
    {
        $data = self::post($url, $data, 'DELETE');
        
        Logger::put($data, 'response');
    }
}