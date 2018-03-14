<?php

namespace GigaAI\Broadcast;
use GigaAI\Storage\Eloquent\Broadcast as BroadcastModel;
use GigaAI\Storage\Eloquent\Group;
use GigaAI\Storage\Eloquent\Instance;
use GigaAI\Core\Config;
use Carbon\Carbon;
use GigaAI\Storage\Eloquent\Node;

/**
 * Handle broadcast with Facebook
 * 
 * @since 3.0
 */
class Broadcast
{
    /**
     * Send Broadcast to Facebook and let they do the rest
     * 
     * @param Broadcast $broadcast
     */
    public static function send(BroadcastModel $broadcast)
    {
        // Send message if people hit send button
        $broadcastProperties = [
            'message_creative_id' => $broadcast->message_creative_id,
            'notification_type'   => $broadcast->notification_type,
            'tag'                 => $broadcast->tags
        ];

        $receivers = $broadcast->getReceivers();
        $receivers = $receivers !== null && is_array($receivers) ? $receivers : ['all'];

        foreach ($receivers as $channel) {

            if ( ! is_null($channel) && $channel !== 'all') {
                $labelId                                = Group::find($channel)->meta['facebook_label_id'];
                $broadcastProperties['custom_label_id'] = $labelId;
            }

            $response = giga_facebook_post('me/broadcast_messages', $broadcastProperties);

            if (isset($response->broadcast_id)) {
                BroadcastModel::create([
                    'instance_id' => $broadcast->instance_id,
                    'parent_id'   => $broadcast->id,
                    'description' => $response->broadcast_id,
                    'content'     => $response->broadcast_id
                ]);
            }
        }
    }

    /**
     * Because each message creative can only attach to 1 broadcast so we'll create message creative each time we send
     * 
     * @param Broadcast $broadcast
     * 
     * @return Mixed
     */
    public static function createMessageCreative(BroadcastModel $broadcast)
    {
        $template             = Node::find($broadcast->content)->answers;
        $messages = [];

        foreach ($template as $answer) {
            if (!in_array($answer['type'], ['text', 'receipt'])) {
                $messages[] = $answer['content'];
            }

            if ($answer['type'] === 'text') {
                // Convert to Dynamic Text
                if (str_contains($answer['content']['text'], '{{')) {
                    $messages[] = [
                    'dynamic_text' => [
                        'text'          => $answer['content']['text'],
                        'fallback_text' => $answer['content']['text']
                    ]
                ];
                } else {
                    $messages[] = $answer['content'];
                }
            }
        }

        // Create message creative
        $response = giga_facebook_post('me/message_creatives', compact('messages'));

        if (isset($response->message_creative_id)) {
            return $response->message_creative_id;
        } else {
            throw new \Exception("Cannot create message creative!", 1);
        }
    }

    public static function getMetrics(BroadcastModel $broadcast)
    {
        if ($broadcast->updated_at < Carbon::now()->subMinutes(5)) {
            $metrics = giga_facebook_get($broadcast->description . '/insights/messages_sent');

            if (isset($metrics->data)) {
                $broadcast->content = $metrics->data;
                $broadcast->save();

                return $metrics->data;
            } else {
                throw new \Exception('Cannot get metrics!');
            }
        } else {
            return $broadcast->content;
        }
    }
}