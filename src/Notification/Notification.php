<?php

namespace GigaAI\Notification;

use GigaAI\Core\Model;
use GigaAI\Http\Request;
use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;
use GigaAI\Storage\Eloquent\Lead;
use GigaAI\Storage\Eloquent\Message;

class Notification
{
    use EasyCall, Singleton;

    private $current_message;

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
    private function getSubscribers()
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

        $regexp = implode('|', $channels);

        $leads = Lead::where('subscribe', 'REGEXP', $regexp)->get();

        $subscribers = [];

        foreach ($leads as $lead) {

            $lead_channels = array_map('trim', explode(',', $lead->subscribe));

            // Array should in another array
            if ($num_args > 1 && ! array_diff($channels, $lead_channels)) {

                $subscribers[] = $lead->user_id;

                continue;
            }

            // One array in another array
            if ($num_args === 1 && array_intersect($channels, $lead_channels))
            {
                $subscribers[] = $lead->user_id;
            }
        }

        return array_unique($subscribers);
    }

    /**
     * Send Message To Leads
     *
     * @param $messages
     * @param $lead_ids
     * @return $this
     */
    private function sendMessageToLeads($messages, $lead_ids, $save = 'save')
    {
        $leads_id = (array) $lead_ids;

        $model      = new Model;

        $messages   = $model->parseWithoutSave($messages);

        if ($save == 'save') {
            $this->current_message = Message::create([
                'to_lead' => implode(',', $leads_id),
                'content' => json_encode($messages)
            ]);
        }

        foreach ($leads_id as $lead_id)
        {
            Request::sendMessages($messages, $lead_id);
        }

        return $this;
    }

    /**
     * Send Message To Channels
     *
     * Parameters:
     *
     * First param: Messages to be send
     *
     * Next params:
     * 1 -> Send to channel 1
     * 1, 2 -> Send to leads in both channel 1 and 2
     * [1,2] -> Send to leads in either channel 1 or 2
     *
     * @throws \Exception
     * @return $this
     */
    private function sendMessageToChannels()
    {
        // Num args = 1: all. > 1 any
        $num_args   = func_num_args();
        $args       = func_get_args();

        $channels = [];

        if ($num_args < 2)
        {
            throw new \Exception('Invalid arguments!');
        }

        $messages = $args[0];

        $channels = [$args[1]];

        if ($num_args > 2)
        {
            array_shift($args);

            $channels = $args;
        }

        $this->current_message = Message::create([
            'to_channel' => implode(',', $channels),
            'content' => json_encode($messages)
        ]);

        $subscribers = @call_user_func_array([$this, 'getSubscribers'], $channels);

        $this->sendMessageToLeads($messages, $subscribers, 'no-save');

        return $this;
    }

    private function at($time)
    {

    }

    private function routines($routine)
    {
        return $this;
    }

    private function startAt($time)
    {
        return $this;
    }

    private function endAt($time)
    {
        return $this;
    }

    private function taggedAs($tag)
    {
        return $this;
    }

    private function many()
    {
        return $this;
    }

    private function once()
    {
        return $this;
    }
}