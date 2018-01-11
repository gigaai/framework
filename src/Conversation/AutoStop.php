<?php

namespace GigaAI\Conversation;

use GigaAI\Storage\Eloquent\Instance;
use GigaAI\Storage\Storage;

class AutoStop
{
    public static function run($event)
    {
        $auto_stop_config = Instance::get('auto_stop');
        
        if ( ! $auto_stop_config)
            return false;
        
        if ($event->sender->id == Conversation::get('page_id')) {
            $administrator_text = null;
            
            $lead = Conversation::get('lead');

            // Empty metadata means that it not sent by bot
            if (isset($event->message->text) && empty($event->message->metadata)) {
                $administrator_text = $event->message->text;
            
                
                // When Auto Stop is already on, and
                if ($lead->auto_stop == 1) {
                    if ($administrator_text == $auto_stop_config['restart_when']) {
                        $lead->data('auto_stop', '');
                    }
                }
                else {
                    if ($administrator_text == $auto_stop_config['stop_when'] || $auto_stop_config['stop_when'] == '*') {
                        $lead->data('auto_stop', 1);

                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    public static function isStopped()
    {
        $auto_stop_config = Instance::get('auto_stop');
        
        if ( ! $auto_stop_config)
            return false;
        
        $lead = Conversation::get('lead');

        return $lead->auto_stop == 1;
    }
}