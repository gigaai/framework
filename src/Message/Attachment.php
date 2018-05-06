<?php

namespace GigaAI\Message;

class Attachment extends Message
{
    /**
     * Current Media Type
     *
     * @var String
     */
    public $mediaType;

    /**
     * All available media types
     *
     * @var Array
     */
    public $availableTypes = ['image', 'audio', 'video', 'file'];

    /**
     * Set some properties to reuse later
     */
    public function __construct($body)
    {
        $class                  = class_basename(get_class($this));
        $this->mediaType        = strtolower($class);

        parent::__construct($body);
    }

    /**
     * Expected format is correct related class format or not.
     *
     * @return bool
     */
    public function expectedFormat()
    {
        return $this->expectedIs($this->mediaType);
    }

    /**
     * Convert to Facebook format to response
     *
     * @return Array
     */
    public function normalize()
    {
        $url = $this->getMediaUrl($this->mediaType);

        return [
            'type'    => $this->mediaType,
            'content' => [
                'attachment' => [
                    'type'    => $this->mediaType,
                    'payload' => [
                        'url'         => $url,
                        'is_reusable' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * Guess media type from URL
     *
     * return mixed
     */
    public function detectMediaType($url)
    {
        foreach ($this->availableTypes as $type) {
            if (starts_with($url, $type . ':')) {
                return $type;
            }
        }

        if (giga_match('%(.jpg|.png|.bmp|.gif|.jpeg|.tiff|.gif)%', $url)) {
            return 'image';
        }

        if (giga_match('%(.avi|.flv|.mp4|.mkv|.3gp|.webm|.vob|.mov|.rm|.rmvb)%', $url)) {
            return 'video';
        }

        if (giga_match('%(.mp3|.wma|.midi|.au)%', $url)) {
            return 'audio';
        }

        return null;
    }

    /**
     * Check current message format
     *
     * @return bool
     */
    public function expectedIs($type)
    {
        // If people set type = message type, return true
        if (is_array($this->body) && isset($this->body['attachment']) && isset($this->body['attachment']['type'])) {
            return true;
        }

        if (is_string($this->body)) {
            $fileExtension = $this->detectMediaType($this->body);
            return $fileExtension === $type;
        }

        // If it's string, maybe it's URL. Check the extension
        if (isset($this->body['attachment']) && is_string($this->body['attachment']['payload']['url'])) {
            $fileExtension = $this->detectMediaType($this->body['attachment']['payload']['url']);
            return $fileExtension === $type;
        }

        return false;
    }

    /**
     * Get media URL
     *
     * @return String
     */
    public function getMediaUrl($mediaType)
    {
        $url = $this->body;

        if (is_array($this->body) && isset($this->body['attachment']['payload']['url']) && is_string($this->body['attachment']['payload']['url'])) {
            $url = $this->body['attachment']['payload']['url'];
        }

        $url = str_replace($mediaType . ':', '', $url);

        return $url;
    }
}
