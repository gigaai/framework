<?php


namespace GigaAI\Core\Responders;


use GigaAI\Core\Rule\Rule;
use GigaAI\Core\Rule\RuleManager;
use GigaAI\Core\User\UserRepositoryInterface;
use GigaAI\Messages\AudioAttachmentMessage;
use GigaAI\Messages\ButtonMessage;
use GigaAI\Messages\FileAttachmentMessage;
use GigaAI\Messages\GenericMessage;
use GigaAI\Messages\ImageMessage;
use GigaAI\Messages\ReceiptMessage;
use GigaAI\Messages\TextMessage;
use GigaAI\Messages\VideoAttachmentMessage;

/**
 * Class AbstractMessageResponder
 * @package GigaAI\Core\Responders
 */
abstract class AbstractMessageResponder implements MessageResponderInterface
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var RuleManager
     */
    protected $ruleManager;

    /**
     * @var Rule[]
     */
    protected $rules = [];

    /**
     * Return a matched rule based on user input
     *
     * @param $input
     *
     * @return Rule|null
     */
    protected abstract function getMatchedRule($input);

    /**
     * Check if a string match with a pattern
     *
     * @param $pattern
     * @param $string
     *
     * @return boolean
     */
    protected abstract function match($pattern, $string);

    public function __construct(
        UserRepositoryInterface $userRepository,
        RuleManager $ruleManager
    ) {
        $this->userRepository = $userRepository;
        $this->ruleManager = $ruleManager;
    }

    /**
     * @inheritdoc
     */
    public function setRules($rules = [])
    {
        $this->rules = $rules;
    }

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
        $waitingRuleId = $this->userRepository->getWaitingRuleId($recipient);

        $waitingRule = $this->ruleManager->getById($waitingRuleId);

        if ($waitingRule && $waitingRule->thenHandler) {
            $response = call_user_func($waitingRule->thenHandler, $input);
            $responseMessages = $this->getResponseMessages($recipient, $response);
            $this->userRepository->unsetWait($recipient);
            return $responseMessages;
        }

        $matchedRule = $this->getMatchedRule($input);

        if (!$matchedRule) {
            return [];
        }

        // Find all answers for the matched rule
        $responseMessages = $this->getResponseMessages($recipient, $matchedRule->response);

        $isWaitMessage = !empty($matchedRule->thenHandler);

        if ($isWaitMessage && count($responseMessages)) {
            $this->userRepository->setWait($recipient, $matchedRule->id);
        }

        return $responseMessages;
    }

    /**
     * Return response messages for user input based on response setting in a rule
     *
     * @param $recipient
     * @param $responseRule
     *
     * @return array
     */
    private function getResponseMessages($recipient, $responseRule)
    {
        // Simple response: one response contain a text, video url, image url...
        if ($this->isSingleResponse($responseRule)) {
            return $this->getMessagesForSingleResponse($recipient, $responseRule);
        } else {
            return $this->getMessagesForMultipleResponse($recipient, $responseRule);
        }
    }

    /**
     * Check if response rule is single response or multiple response
     *
     * @param $response
     *
     * @return bool
     */
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

    /**
     * Return messages for single response rule
     *
     * @param $recipient
     * @param $response
     *
     * @return array
     */
    private function getMessagesForSingleResponse($recipient, $response)
    {
        // The response is some kind of URLs or Text
        if (is_string($response)) {
            // Send as image
            if (strpos($response, 'image:') !== false) {
                $imageUrl = str_replace('image:', '', $response);

                return [new ImageMessage($recipient, $imageUrl)];
            }

            // Send as file
            if (strpos($response, 'file:') !== false) {
                $fileUrl = str_replace('file:', '', $response);

                return [new FileAttachmentMessage($recipient, $fileUrl)];
            }

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

    /**
     * Return messages for multiple response rule
     *
     * @param $recipient
     * @param $responses
     *
     * @return array
     */
    private function getMessagesForMultipleResponse($recipient, $responses)
    {
        $responseMessages = [];

        foreach ($responses as $response) {
            $responseMessages = array_merge($responseMessages, $this->getMessagesForSingleResponse($recipient, $response));
        }

        return $responseMessages;
    }

    /**
     * Return message from URL response rule
     *
     * @param $recipient
     * @param $url
     *
     * @return AudioAttachmentMessage|FileAttachmentMessage|ImageMessage|VideoAttachmentMessage
     */
    private function createMessageFromUrl($recipient, $url)
    {
        if ($this->match('%(.jpg|.png|.bmp|.gif|.jpeg|.tiff|.gif)%', $url))
            return new ImageMessage($recipient, $url);

        if ($this->match('%(.avi|.flv|.mp4|.mkv|.3gp|.webm|.vob|.mov|.rm|.rmvb)%', $url))
            return new VideoAttachmentMessage($recipient, $url);

        if ($this->match('%(.mp3|.wma|.midi|.au)%', $url))
            return new AudioAttachmentMessage($recipient, $url);

        return new FileAttachmentMessage($recipient, $url);
    }
}