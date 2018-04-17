<?php

namespace GigaAI\Core;

use GigaAI\Conversation\Conversation;

class Matching
{
    public static function getId($id, $type = 'asid')
    {
        $edge = $type === 'asid' ? 'ids_for_apps' : 'ids_for_pages';
        
        $appSecretProof = self::getAppSecretProof();
        $accessToken = Config::get('access_token');
        
        $response = giga_remote_get("https://graph.facebook.com/v2.11/{$id}/{$edge}?access_token={$accessToken}&appsecret_proof={$appSecretProof}");
        
        return $response->data;
    }

    public static function getAsids($psid)
    {
        return self::getId($psid, 'asid');
    }

    public static function getPsids($asid)
    {
        return self::getId($asid, 'psid');
    }

    public static function matchCurrentLead()
    {
        $lead = Conversation::get('lead');
        
        if ( ! $lead->isLinked()) {
            $ids = self::getAsids($lead->user_id);

            $ids = array_map(function ($app) {
                return $app->id;
            }, $ids);

            $user = \App\User::whereIn('provider_id', $ids)->first();
            $lead->data('linked_account', $user->id);

            return $user;
        }

        return null;
    }

    public static function getAppSecretProof()
    {
        return hash_hmac('sha256', Config::get('access_token'), Config::get('app_secret')); 
    }
}