<?php

namespace GigaAI\Core;

use GigaAI\Storage\Storage;

class AccountLinking
{
    public static function process($event)
    {
        // Never happen, but we have to check to make sure
        if ($event->sender->id == Config::get('page_id')) {
            return;
        }
        
        // Link Lead with User
        if ($event->account_linking->status === 'linked') {
            $authorization_code = $event->account_linking->authorization_code;
            
            $user_id = ltrim($authorization_code, 'user_id:');
            
            self::linkWithExistingUser($event->sender->id, $user_id);
        } // Unlink, Logout user
        else {
            self::unlinkWithExistingUser($event->sender->id);
        }
        
        return true;
    }
    
    private static function linkWithExistingUser($lead_id, $user_id)
    {
        Storage::set($lead_id, 'linked_account', $user_id);
    }
    
    private static function unlinkWithExistingUser($lead_id)
    {
        Storage::set($lead_id, 'linked_account', '');
    }
}