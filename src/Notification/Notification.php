<?php

namespace GigaAI\Notification;

use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;

class Notification
{
    use EasyCall, Singleton;

    public function addSubscribers($user_ids, $channels = 1)
    {

    }

    public function getSubscribers($user_ids, $channels)
    {

    }

    public function messageTo($user_ids, $messages)
    {
        return $this;
    }

    public function messageToChannels($channels, $messages)
    {
        return $this;
    }

    public function at($time)
    {

    }

    public function routines($routine)
    {
        return $this;
    }

    public function startAt($time)
    {
        return $this;
    }

    public function endAt($time)
    {
        return $this;
    }

    public function taggedAs($tag)
    {
        return $this;
    }

    public function many()
    {
        return $this;
    }

    public function once()
    {
        return $this;
    }
}