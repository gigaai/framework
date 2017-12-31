<?php

namespace GigaAI\Message;

abstract class Message
{
    public $body;

    public function __construct($body)
    {
        $this->body = $body;
    }

    /**
     * Child class should override this method
     *
     * @return array
     **/
    abstract public function normalize();

    /**
     * Expected format to be parsed
     *
     * @return bool
     */
    abstract public function expectedFormat();

    /**
     * Load the message to parse and return
     *
     * @param $body
     *
     * @return mixed
     */
    public static function load($body, $flags = [])
    {
        $instance = new static($body);

        if (!isset($flags['skip_detection']) || !$flags['skip_detection']) {
            if (!$instance->expectedFormat()) {
                return false;
            }
        }

        return $instance->normalize();
    }

    public function sanitizeButtons($buttons)
    {
        $buttonProperties = [
            'web_url'        => ['url', 'title', 'webview_height_ratio', 'messenger_extensions', 'fallback_url'],
            'postback'       => ['title', 'payload'],
            'element_share'  => ['share_contents'],
            'account_unlink' => [],
            'account_link'   => ['url'],
            'game_play'      => ['title', 'payload', 'game_metadata'],
            'phone_number'   => ['title', 'payload'],
            'payment'        => ['title', 'payload', 'payment_summary', 'price_list'],
        ];

        return collect($buttons)->map(function ($button) use ($buttonProperties) {
            if (isset($button['title'])) {
                $button['title'] = substr($button['title'], 0, 20);
            }

            $properties = array_merge(['type'], $buttonProperties[$button['type']]);

            return array_only($button, $properties);
        })->toArray();
    }

    public function sanitizeElements($elements)
    {
        return collect($elements, function ($element) {
            $element['title'] = substr($element['title'], 0, 80);

            if (isset($element['subtitle'])) {
                $element['subtitle'] = substr($element['subtitle'], 0, 80);
            }

            if (isset($element['image_url']) && !filter_var($element['image_url'], FILTER_VALIDATE_URL)) {
                unset($element['image_url']);
            }

            if (isset($element['buttons'])) {
                $element['buttons'] = $this->sanitizeButtons($element['buttons']);
            }
        })->toArray();
    }
}
