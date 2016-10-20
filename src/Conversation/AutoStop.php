<?php

namespace GigaAI\Conversation;


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
}