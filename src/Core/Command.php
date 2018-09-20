<?php

namespace GigaAI\Core;

use GigaAI\Conversation\Conversation;
use GigaAI\Subscription\Subscription;
use GigaAI\Storage\Storage;
use GigaAI\Http\HandoverProtocol;
use GigaAI\Shortcodes\Shortcode;

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

        $lead = Conversation::get('lead');

        if (!empty($lead)) {
            if ($type === 'add') {
                return $lead->channels()->attach($channels);
            }

            return $lead->channels()->detach($channels);
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
        $lead = Conversation::get('lead');

        if ($value[0] === '#') {
            $value  = Shortcode::parseVariables($value);
        }

        if (in_array($value, ['$input', '#input', '[input]'])) {
            $value = Conversation::get('received_input');
        }

        if (!is_string($value)) {
            $value = json_encode($value);
        }

        $lead->data($field, $value);
    }

    public static function chatWithHuman()
    {
        $handover = new HandoverProtocol;
        $handover->passToInbox();
    }
}
