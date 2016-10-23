<?php

namespace GigaAI\Notification;

use Carbon\Carbon;
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

    private function create($notification)
    {
        $this->current_message = Message::create($notification);

        return $this;
    }

    /**
     * Find by unique id and check for start_at and end_at
     *
     * @param $unique_id
     * @return $this
     * @throws \Exception
     */
    private function find($unique_id)
    {
        $now = Carbon::now();

        $this->current_message = Message::where('unique_id', $unique_id)->first();

        if (isset($this->current_message->start_at) && $this->current_message->start_at < $now) {
            unset($this->current_message);
            throw new \Exception('This notification is not ready to send. Please be patient!');
        }

        if (isset($this->current_message->end_at) && $this->current_message->end_at > $now) {
            unset($this->current_message);
            throw new \Exception('This notification has expired. Please create another!');
        }

        return $this;
    }

    /**
     * Send Message with prepared data
     *
     * @throws \Exception
     */
    private function send()
    {
        if ( ! $this->current_message) {
            throw new \Exception('No notification found!');
        }

        $notification = $this->current_message;

        $call = 'sendMessageToChannels';

        $to = $notification->to_channel;

        if (empty($to))
        {
            $to = $notification->to_lead;
            $call = 'sendMessageToLeads';
        }

        if (is_numeric($notification->send_limit) && $notification->sent_count >= $notification->send_limit)
        {
            throw new \Exception('You have already reached limit notification to send!');
        }

        @call_user_func_array([$this, $call], [$notification->content, $to]);

        $notification->sent_count++;

        $notification->save();
    }

    /**
     * Get subscribers of channels
     *
     * Parameters:
     *
     * 1 -> returns any subscribers in channel 1
     * 'foo' -> returns any subscribers in channel foo
     * [1, 'foo'] -> returns any subscribers in both channel 1 and foo
     * '1, foo' -> returns any subscriber in either channel 1 or foo
     */
    private function getSubscribers($channels = 1)
    {
        $all = is_array($channels);

        if (is_string($channels) || is_numeric($channels))
            $channels = explode(',', $channels);

        $channels = array_map('trim', $channels);

        $regexp = implode('|', $channels);

        $leads = Lead::where('subscribe', 'REGEXP', $regexp)->get();

        $subscribers = [];

        foreach ($leads as $lead) {

            $lead_channels = array_map('trim', explode(',', $lead->subscribe));

            // Array should in another array
            if ($all && ! array_diff($channels, $lead_channels)) {

                $subscribers[] = $lead->user_id;

                continue;
            }

            // One element of array in another array
            if ( ! $all && array_intersect($channels, $lead_channels))
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
    private function sendMessageToLeads($messages, $lead_ids)
    {
        $lead_ids = (array) $lead_ids;

        foreach ($lead_ids as $lead_id)
        {
            Request::sendMessages($messages, $lead_id);
        }
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
     * 1,2 or '1,2' -> Send to leads in both channel 1 and 2
     * [1,2] -> Send to leads in either channel 1 or 2
     *
     * @throws \Exception
     * @return $this
     */
    private function sendMessageToChannels($messages, $channels)
    {
        $subscribers = $this->getSubscribers($channels);

        $this->sendMessageToLeads($messages, $subscribers);
    }
}