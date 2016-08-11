<?php

namespace GigaAI\Core;

class Parser
{
	/**
	 * Todo: Rewrite this method
	 *
	 * @param $answer
	 * @return array
	 */
	public static function parseAnswer($answer)
	{
		$message = array(
			'attachment' => array()
		);

		if (is_array($answer) && ! array_key_exists('quick_replies', $answer))
		{
			$message['attachment']['type'] = 'template';

			$message['attachment']['payload'] = $answer;

			$template_type = 'generic';

			// If it's generic and super short hand.
			if ( ! empty($answer[0]) && array_key_exists('title', $answer[0]))
			{
				$message['attachment']['payload'] = array();
				$message['attachment']['payload']['elements'] = $answer;
			}

			if (isset($answer['buttons']) && ! array_key_exists('title', $answer))
				$template_type = 'button';

			if (isset($answer['order_number']))
				$template_type = 'receipt';

			// Detect payload type here
			if ( ! isset($answer['template_type']))
				$message['attachment']['payload']['template_type'] = $template_type;
		}

		if (is_array($answer) && array_key_exists('quick_replies', $answer))
		{
			$quick_replies = $answer['quick_replies'];

			$content = self::parseAnswer($answer[0]);

			return $content + array('quick_replies' => $quick_replies);
		}

		if (self::isAttachmentMessage($answer))
		{
			$message['attachment'] = array();

			$message['attachment']['type']              = $answer['type'];
			$message['attachment']['payload']['url']    = $answer['url'];
		}

		if (is_string($answer))
		{
			// If it's a text link with prefix `image:` `audio:` `video:` `file:`
			foreach (array('image', 'audio', 'video', 'file') as $type)
			{
				if (strpos($answer, $type . ':') !== false)
				{
					$url = ltrim($answer, $type . ':');

					return self::parseAnswer(compact('type', 'url'));
				}
			}

			// If it's URL. Detect is audio, video, image...
			if (filter_var($answer, FILTER_VALIDATE_URL))
			{
				return self::parseAnswer(array(
					'type'  => self::detectAttachmentType($answer),
					'url'   => $answer
				));
			}

			// If it's plain text
			unset($message['attachment']);

			$message['text'] = $answer;
		}

		return $message;
	}

	public static function detectAttachmentType($url)
	{
		if (giga_match('%(.jpg|.png|.bmp|.gif|.jpeg|.tiff|.gif)%', $url))
			return 'image';

		if (giga_match('%(.avi|.flv|.mp4|.mkv|.3gp|.webm|.vob|.mov|.rm|.rmvb)%', $url))
			return 'video';

		if (giga_match('%(.mp3|.wma|.midi|.au)%', $url))
			return 'audio';

		return 'file';
	}

	public static function isAttachmentMessage($answer)
	{
		return is_array($answer) &&
				isset($answer['type']) &&
				in_array($answer['type'], array('image', 'video', 'audio', 'file'));
	}

	public static function parseShortcodes($response, $dictionary = array())
	{
		if (empty($dictionary))
			return $response;
		
		foreach ($dictionary as $shortcode => $value)
		{
			unset($dictionary[$shortcode]);

			$dictionary["[$shortcode]"] = $value;
		}

		// Replace in Text
		if ( ! empty($response['text']))
			$response['text'] = strtr($response['text'], $dictionary);

		// Replace in Button
		if (! empty($response['attachment']['text']))
			$response['attachment']['text'] = strtr($response['text'], $dictionary);

		// Replace in Generic
		return $response;
	}
}