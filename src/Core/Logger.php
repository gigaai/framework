<?php

namespace GigaAI\Core;

use GigaAI\Conversation\Conversation;
use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;

class Logger
{
    use Singleton, EasyCall;
    
    private function getPath()
    {
        $path = Config::get('log_path');
        
        if ( ! isset($path) || empty($path) || ! is_readable($path) || ! is_writable($path)) {
            return false;
        }
        
        return $path;
    }
   
    private function put($data, $type = 'incoming')
    {
        if ( empty($data)) {
            return;
        }
        
        $path = $this->getPath();
        $token = Conversation::get('token');
        
        if ( ! $path) {
            return false;
        }
       
        $persisted = $this->get();
        if ( ! empty($persisted) && count($persisted) > 20) {
            $persisted = [];
        }
        
        if ( ! isset($persisted[$token])) {
            $persisted[$token] = [];
            $persisted[$token]['timestamp'] = date('Y-m-d H:i:s');
        }
        
        $persisted[$token][$type] = $data;
        
        file_put_contents($path, json_encode($persisted));
        
        return $persisted;
    }
    
    private function get($token = '')
    {
        $path = $this->getPath();
        
        if ( ! $path || ! file_exists($path)) {
            return [];
        }
        
        $persisted = file_get_contents($path);
        $persisted = json_decode($persisted, true);
        
        if (! empty($token) && isset($persisted[$token])) {
            return $persisted[$token];
        }
        
        return $persisted;
    }
    
    private function clear()
    {
        $path = $this->getPath();
        
        if ( ! $path) {
            return false;
        }
        
        return file_put_contents($path, '');
    }
}