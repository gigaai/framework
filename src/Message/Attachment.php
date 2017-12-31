<?php

namespace GigaAI\Message;

class Attachment extends Message
{
    public $mediaType;

    public function expectedFormat()
    {
        $class           = get_class($this);
        $class           = class_basename($class);
        $this->mediaType = strtolower($class);

        return $this->expectedIs($this->mediaType);
    }

    public function normalize()
    {
        $url = $this->getMediaUrl($this->mediaType);

        return [
            'attachment' => [
                'type'    => $this->mediaType,
                'payload' => [
                    'url'         => $url,
                    'is_reusable' => true
                ]
            ]
        ];
    }
}
