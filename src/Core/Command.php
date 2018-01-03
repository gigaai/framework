<?php

namespace GigaAI\Core;

use GigaAI\Conversation\Conversation;
use GigaAI\Subscription\Subscription;
use GigaAI\Storage\Storage;

class Command
{
    public static function run($command, $params)
    {
        @call_user_func_array(['\\GigaAI\\Core\\Command', $command], $params);
    }

    protected static function setChannel($channels, $type = 'add')
    {
        // Remove all non selected channels
        $channels = array_filter($channels);
        $channels = array_unique(array_keys($channels));

        $lead_id = Conversation::get('lead_id');

        if (!empty($lead_id)) {
            Subscription::setSubscriptionChannel($lead_id, $channels, $type);
        }
    }

    public static function addChannel($channels)
    {
        self::setChannel($channels);
    }

    public static function removeChannel($channels)
    {
        self::setChannel($channels, 'remove');
    }

    public static function updateLead($field, $value)
    {
        $lead_id    = Conversation::get('lead_id');

        if ($value === '$input' || $value === '[input]') {
            $value = Conversation::get('received_input');
        }

        if (!is_string($value)) {
            $value = json_encode($value);
        }

        Storage::set($lead_id, $field, $value);
    }
}
