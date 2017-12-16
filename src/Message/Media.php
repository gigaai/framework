<?php

namespace GigaAI\Message;

class Media extends Message
{
    public function expectedFormat()
    {
        return is_string($this->body) && (
                filter_var($this->body, FILTER_VALIDATE_URL) ||
                str_contains($this->body, ['image:', 'audio:', 'video:', 'file:'])
            );
    }

    public function normalize()
    {
        $media_type = $this->detectMediaType($this->body);

        foreach (['image', 'audio', 'video', 'file'] as $type) {
            if (strpos($this->body, $type . ':') !== false) {
                $this->body = ltrim($this->body, $type . ':');
            }
        }

        return [
            'attachment' => [
                'type'    => 'template',
                'payload' => [
                    'template_type' => 'media',
                    'elements'      => [
                        'media_type' => $media_type,
                        'url'        => $this->body,
                    ],
                ],
            ],
        ];
    }

    public function detectMediaType($url)
    {
        if (giga_match('%(.jpg|.png|.bmp|.gif|.jpeg|.tiff|.gif)%', $url) || strpos($url, 'image') !== false) {
            return 'image';
        }

        if (giga_match('%(.avi|.flv|.mp4|.mkv|.3gp|.webm|.vob|.mov|.rm|.rmvb)%', $url) || strpos($url,
                'video') !== false) {
            return 'video';
        }

        if (giga_match('%(.mp3|.wma|.midi|.au)%', $url) || strpos($url, 'audio') !== false) {
            return 'audio';
        }

        if (strpos($url, 'file') !== false) {
            return 'file';
        }

        return null;
    }
}