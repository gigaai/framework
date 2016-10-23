<?php

namespace GigaAI\Conversation;

use GigaAI\Storage\Eloquent\Lead;

class AutoStop
{
    /**
     * Start the conversation when human send any character
     *
     * @var string
     */
    public $start_when = '*';

    /**
     * Stop the conversation when human send smile
     *
     * @var string
     */
    public $stop_when = ':)';

    /**
     * Block current user when
     *
     * @var string
     */
    public $block_when = '';


    public static function init()
    {

    }

    public static function getStatus($lead_id = null)
    {
        if (is_null($lead_id))
            $lead_id = Conversation::get('lead_id');

        return Lead::where('lead_id', $lead_id)->first()->pluck('auto_stop');
    }


}