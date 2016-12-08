<?php

namespace GigaAI\Conversation;

use GigaAI\Core\Config;
use GigaAI\Storage\Storage;

class AutoStop
{
    public static function run($event)
    {
        $auto_stop_config = Config::get('auto_stop');
        
        if ( ! $auto_stop_config)
            return false;
        
        if ($event->sender->id == Config::get('page_id')) {
            $administrator_text = null;
            $lead_id = $event->recipient->id;
        
            // Empty metadata means that it not sent by bot
            if (isset($event->message->text) && empty($event->message->metadata)) {
                $administrator_text = $event->message->text;
            
                $auto_stop = Storage::get($lead_id, 'auto_stop');
                
                // When Auto Stop is already on, and
                if ($auto_stop == 1) {
                    if ($administrator_text == $auto_stop_config['restart_when']) {
                        Storage::set($lead_id, 'auto_stop', '');
                    }
                }
                else {
                    if ($administrator_text == $auto_stop_config['stop_when'] || $auto_stop_config['stop_when'] == '*') {
                        Storage::set($lead_id, 'auto_stop', 1);
                        
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    public static function isStopped()
    {
        $auto_stop_config = Config::get('auto_stop');
    
        if ( ! $auto_stop_config)
            return false;
        
        $auto_stop = Storage::get(Conversation::get('lead_id'), 'auto_stop');
        
        return $auto_stop == 1;
    }
}