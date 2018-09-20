<?php

namespace GigaAI\Http;
use GigaAI\Conversation\Conversation;

class HandoverProtocol
{
    /**
     * Pass the message to Inbox
     * 
     * @return Json
     */
    public function passToInbox()
    {
        $lead_id = Conversation::get('lead_id');
        
        return giga_facebook_post('me/pass_thread_control', [

            // Pass current lead to Page Inbox
            'recipient' => [
                'id' => $lead_id
            ],
            
            // Target App ID is Page Inbox
            'target_app_id' => '263902037430900',

            // We don't need meta data
            'metadata' => ''
        ]);
    }
}