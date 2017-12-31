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
    public static function load($body)
    {
        $instance = new static($body);

        if (!$instance->expectedFormat()) {
            return false;
        }

        return $instance->normalize();
    }

    public function detectMediaType($url)
    {
        if (giga_match('%(.jpg|.png|.bmp|.gif|.jpeg|.tiff|.gif)%', $url) || str_contains($url, 'image:')) {
            return 'image';
        }

        if (giga_match('%(.avi|.flv|.mp4|.mkv|.3gp|.webm|.vob|.mov|.rm|.rmvb)%', $url) || str_contains($url, 'video:')) {
            return 'video';
        }

        if (giga_match('%(.mp3|.wma|.midi|.au)%', $url) || str_contains($url, 'audio:')) {
            return 'audio';
        }

        if (str_contains($url, 'file:')) {
            return 'file';
        }

        return null;
    }

    public function expectedIs($type)
    {
        if (is_array($this->body) && isset($this->body['type']) && $this->body['type'] === $type) {
            return true;
        }

        if (is_string($this->body)) {
            $fileExtension = $this->detectMediaType($this->body);

            return $fileExtension === null || $fileExtension === $type;
        }

        return false;
    }

    public function getMediaUrl($mediaType)
    {
        $url = $this->body;

        if (is_array($this->body)) {
            if (isset($this->body['content']) && is_string($this->body['content'])) {
                $url = $this->body['content'];
            }

            if (isset($this->body['content']['url'])) {
                $url = $this->body['content']['url'];
            }
        }

        if (str_contains($url, $mediaType . ':')) {
            $url = ltrim($url, $mediaType . ':');
        }

        return $url;
    }
}
