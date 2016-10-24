<?php

namespace GigaAI\Subscription;

use Carbon\Carbon;
use GigaAI\Core\Model;
use GigaAI\Http\Request;
use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;
use GigaAI\Storage\Eloquent\Lead;
use GigaAI\Storage\Eloquent\Message;

class Subscription
{
    use EasyCall, Singleton;

    /**
     * Current notification message
     * 
     * @var Message
     */
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
        if (is_array($user_ids))
        {
            if (is_null($channels)) {
                foreach ($user_ids as $user_id => $channels) {
                    $this->addSubscribers($user_id, $channels);
                }
            }
            else {
                foreach ($user_ids as $user_id) {
                    $this->addSubscribers($user_id, $channels);
                }
            }
            return;
        }

        // Merge lead channels with new channels
        $lead = Lead::where('user_id', $user_ids)->first();

        if (is_null($lead))
            return;

        // Convert channels to array
        $channels       = is_string($channels) ? array_map('trim', explode(',', $channels)) : $channels;

        // Convert lead->subscribe to array
        $lead_channels  = ( ! empty($lead->subscribe)) ? array_map('trim', explode(',', $lead->subscribe)) : [];

        // Merge channels and lead->subscribe then convert to csv
        $lead->subscribe = implode(',', array_unique(array_merge($lead_channels, $channels)));

        // Update the lead
        $lead->save();
    }

    /**
     * Create a subscription
     * 
     * @param  array $subscription
     * @throws \Exception
     * @return $this
     */
    private function create($subscription)
    {
        if (empty($subscription['content']) || (empty($subscription['to_lead']) && empty($subscription['to_channel']))) {
            throw new \Exception('Cannot create subscription. A required field is missing!');
        }

        // If this notification has already been created. Just find the notification
        if (! empty($subscription['unique_id'])) {
            $this->find($subscription['unique_id']);
        }

        // Create the notification
        if (is_null($this->current_message) || ! $this->current_message) {
            $model = new Model;
            $subscription['content'] = $model->parseWithoutSave($subscription['content']);

            $this->current_message = Message::firstOrCreate($subscription);
        }

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

        $message = Message::where('unique_id', $unique_id)->first();

        if (isset($message->start_at) && $message->start_at > $now) {
            throw new \Exception('This subscription is not ready to send. Please be patient!');
        }

        if (isset($message->end_at) && $message->end_at < $now) {
            throw new \Exception('This subscription has expired. Please create another!');
        }

        $this->current_message = $message;

        return $this;
    }

    /**
     * Send Message with prepared data
     *
     * @throws \Exception
     */
    private function send()
    {
        if ( ! $this->current_message || is_null($this->current_message)) {
            throw new \Exception('No subscription found!');
        }

        $subscription = $this->current_message;

        $call = 'sendMessageToChannels';

        $to = $subscription->to_channel;

        if (empty($to))
        {
            $to = $subscription->to_lead;
            $call = 'sendMessageToLeads';
        }

        if (is_numeric($subscription->send_limit) && $subscription->sent_count >= $subscription->send_limit)
        {
            throw new \Exception('You have already reached limit notification to send!');
        }

        @call_user_func_array([$this, $call], [$subscription->content, $to]);

        $subscription->sent_count++;
        $subscription->sent_at = Carbon::now();

        $subscription->save();
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