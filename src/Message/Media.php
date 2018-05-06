<?php

namespace GigaAI\Message;

class Media extends Message
{
    public function expectedFormat()
    {
        if (is_string($this->body) && (
                filter_var($this->body, FILTER_VALIDATE_URL) ||
                str_contains($this->body, ['image:', 'audio:', 'video:', 'file:'])
            )) {
            return true;
        }

        if (is_array($this->body)) {
            if (isset($this->body[0]) && is_string($this->body[0]) &&
                (filter_var($this->body[0], FILTER_VALIDATE_URL) ||
                 str_contains($this->body[0], ['image:', 'audio:', 'video:', 'file:']))
            ) {
                return true;
            }

            if (isset($this->body['url']) && is_string($this->body['url']) &&
                (filter_var($this->body['url'], FILTER_VALIDATE_URL) ||
                 str_contains($this->body['url'], ['image:', 'audio:', 'video:', 'file:']))
            ) {
                return true;
            }
        }

        return false;
    }

    public function normalize()
    {
        $url = '';
        if (is_string($this->body)) {
            $url = $this->body;
        }

        if (is_array($this->body) && isset($this->body[0]) && is_string($this->body[0])) {
            $url = $this->body[0];
        }

        if (is_array($this->body) && isset($this->body['url'])) {
            $url = $this->body['url'];
        }

        $media_type = $this->detectMediaType($url);

        $url = str_replace(['image:', 'audio:', 'video:', 'file:'], '', $url);

        $output = [
            'type'    => $media_type,
            'content' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'template_type' => 'media',
                        'elements'      => [
                            [
                                'media_type' => $media_type,
                                'url'        => $url,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if (is_array($this->body) && isset($this->body['buttons'])) {
            $output['content']['attachment']['payload']['elements'][0]['buttons'] = $this->body['buttons'];
        }

        return $output;
    }
}
