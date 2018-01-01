<?php

namespace GigaAI\Core;

use GigaAI\Storage\Storage;

class AccountLinking
{
    /**
     * Process the account linking
     * 
     * @param Array $event
     * @return bool
     */
    public static function process($event)
    {
        // Link Lead with User
        if ($event->account_linking->status === 'linked') {
            $authorization_code = $event->account_linking->authorization_code;
            
            $user_id = ltrim($authorization_code, 'user_id:');
            
            return self::linkWithExistingUser($event->sender->id, $user_id);
        } // Unlink, Logout user
        
        return self::unlinkWithExistingUser($event->sender->id);
    }
    
    /**
     * Set the linked_account field for current user
     * 
     * @param String $lead_id Facebook Lead ID
     * @param String $user_id User ID
     */
    private static function linkWithExistingUser($lead_id, $user_id)
    {
        return Storage::set($lead_id, 'linked_account', $user_id);
    }
    
    /**
     * Unset the linked_account field for current user
     * 
     * @param String $lead_id Facebook Lead ID
     */
    private static function unlinkWithExistingUser($lead_id)
    {
        return Storage::set($lead_id, 'linked_account', '');
    }
}