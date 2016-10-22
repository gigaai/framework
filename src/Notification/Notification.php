<?php

namespace GigaAI\Notification;

use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;
use GigaAI\Storage\Eloquent\Lead;

class Notification
{
    use EasyCall, Singleton;

    /**
     * Add subscribers to channels
     *
     * @param mixed $user_ids
     * @param mixed $channels
     *
     * @return mixed
     */
    private function addSubscribers($user_ids, $channels = null)
    {
        if (is_array($user_ids) && is_null($channels))
        {
            foreach ($user_ids as $user_id => $channels) {
                $this->addSubscribers($user_id, $channels);
            }

            return;
        }

        $channels = is_array($channels) ? implode(',', $channels) : $channels;

        return Lead::whereIn('user_id', $user_ids)->update([
            'subscribe' => $channels
        ]);
    }

    /**
     * Get subscribers of channels
     *
     * Parameters:
     *
     * 1 -> returns any subscribers in channel 1
     * 'foo' -> returns any subscribers in channel foo
     * [1, 'foo'] -> returns any subscribers in either channel 1 or foo
     * 1, 'foo' -> returns any subscriber in both channel 1 and foo
     */
    public function getSubscribers()
    {
        // Num args = 1: all. > 1 any
        $num_args   = func_num_args();
        $args       = func_get_args();

        $channels = [];

        // Any syntax
        if ($num_args === 1) {
            if (is_array($args[0]))
                $channels = $args[0];

            if (is_string($args[0]) || is_numeric($args[0]))
                $channels = [$args[0]];

        } else {
            $channels       = $args;
        }

        $channels = array_map('trim', $channels);

        $regexp = $this->buildSearchSyntax($channels);

        $leads = Lead::where('subscribe', 'REGEXP', $regexp)->get();

        $subscribers = [];

        foreach ($leads as $lead) {

            $lead_channels = array_map('trim', explode(',', $lead->subscribe));

            // Array should in another array
            if ($num_args > 1 && ! array_diff($channels, $lead_channels)) {

                $subscribers = $lead;

                continue;
            }

            // One array in another array
            if ($num_args === 1 && array_intersect($channels, $lead_channels))
            {
                $subscribers = $lead;
            }
        }

        return $subscribers;
    }

    private function buildSearchSyntax($channels = [])
    {
        return implode('|', $channels);
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