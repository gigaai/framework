<?php


namespace GigaAI\Core\Responders;


use GigaAI\Core\Rule\Rule;
use GigaAI\Messages\AudioAttachmentMessage;
use GigaAI\Messages\ButtonMessage;
use GigaAI\Messages\FileAttachmentMessage;
use GigaAI\Messages\GenericMessage;
use GigaAI\Messages\ImageMessage;
use GigaAI\Messages\ReceiptMessage;
use GigaAI\Messages\TextMessage;
use GigaAI\Messages\VideoAttachmentMessage;


/**
 * Class DefaultMessageResponder
 *
 * @package GigaAI\Core\Responders
 */
class DefaultMessageResponder extends AbstractMessageResponder
{
    /**
     * Make a new Message from $input & list of rules
     * Return response message & a flag indicate that whether or not that is a wait message
     *
     * @param $recipient
     * @param $input
     *
     * @return array
     */
    public function response($recipient, $input)
    {
        $matchedRule = $this->getMatchedRule($input);

        if (!$matchedRule) {
            return [null, false];
        }

        $isWaitMessage = !empty($matchedRule->thenHandler);

        // Find all answers for the matched rule
        $responseMessages = $this->getResponseMessagesForRule($recipient, $matchedRule);

        return [$responseMessages, $isWaitMessage];
    }

    private function getMatchedRule($input)
    {
        $matchedRule = null;

        foreach ($this->rules as $rule) {
            /** @var Rule $rule */
            if ($this->match($rule->request, $input)) {
                $matchedRule = $rule;
                break;
            }
        }

        //Todo: Add default rule
        if (!$matchedRule) {
            $defaultRule = array_filter($this->rules, function($rule) {
                /** @var Rule $rule */
                return $rule->request === 'default:';
            });

            if ($defaultRule) {
                $matchedRule = reset($defaultRule);
            }
        }

        return $matchedRule;
    }

    private function getResponseMessagesForRule($recipient, Rule $rule)
    {
        // Simple response: one response contain a text, video url, image url...
        if ($this->isSingleResponse($rule->response)) {
            return $this->getMessagesForSingleResponse($recipient, $rule->response);
        } else {
            return $this->getMessagesForMultipleResponse($recipient, $rule->response);
        }
    }

    private function isSingleResponse($response)
    {
        if (is_string($response)) {
            return true;
        }

        // Some single responses has array format
        if (array_key_exists('buttons', $response) // Button message
            || array_key_exists('recipient_name', $response) // Receipt message
            || (count($response) > 1 && isset($response[0]['title']) && isset($response[0]['buttons']))// Generic message
        ) {
            return true;
        }

        return false;
    }

    private function getMessagesForSingleResponse($recipient, $response)
    {
        // Todo: move this logic to other class
        // The response is some kind of URLs or Text
        if (is_string($response)) {

            if (filter_var($response, FILTER_VALIDATE_URL)) {
                return [$this->createMessageFromUrl($recipient, $response)];
            } else {
                return [new TextMessage($recipient, $response)];
            }
        }

        if (array_key_exists('buttons', $response) && array_key_exists('text', $response)) {
            return [new ButtonMessage($recipient, $response['text'], $response['buttons'])];
        } elseif (count($response) > 1 && isset($response[0]['title']) && isset($response[0]['buttons'])) {
            return [new GenericMessage($recipient, $response)];
        } elseif (array_key_exists('recipient_name', $response)) {
            return [
                new ReceiptMessage(
                    $recipient,
                    $response['recipient_name'],
                    $response['order_number'],
                    $response['currency'],
                    $response['payment_method'],
                    $response['order_url'],
                    $response['timestamp'],
                    $response['elements'],
                    $response['address'],
                    $response['summary'],
                    $response['adjustments']
                )
            ];
        }

        return [];
    }

    private function getMessagesForMultipleResponse($recipient, $responses)
    {
        $responseMessages = [];

        foreach ($responses as $response) {
            $responseMessages = array_merge($responseMessages, $this->getMessagesForSingleResponse($recipient, $response));
        }

        return $responseMessages;
    }

    private function createMessageFromUrl($recipient, $url)
    {
        if (giga_match('%(.jpg|.png|.bmp|.gif|.jpeg|.tiff|.gif)%', $url))
            return new ImageMessage($recipient, $url);

        if (giga_match('%(.avi|.flv|.mp4|.mkv|.3gp|.webm|.vob|.mov|.rm|.rmvb)%', $url))
            return new VideoAttachmentMessage($recipient, $url);

        if (giga_match('%(.mp3|.wma|.midi|.au)%', $url))
            return new AudioAttachmentMessage($recipient, $url);

        return new FileAttachmentMessage($recipient, $url);
    }

    private function match($pattern, $string)
    {
        if (strpos($pattern, 'regex:') !== false)
        {
            $pattern = str_replace('regex:', '', $pattern);

            return preg_match($pattern, $string);
        }

        $pattern = strtr($pattern, array(
            '%' => '[\s\S]*',
            '?' => '\?',
            '*' => '\*',
            '+' => '\+',
            '.' => '\.'
        ));

        return preg_match("/^$pattern$/i", $string);
    }
}