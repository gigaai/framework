<?php


namespace GigaAI;


use GigaAI\Core\MessageSender;
use GigaAI\Core\Responders\MessageResponderInterface;
use GigaAI\Core\Rule\RuleManager;

/**
 * Class MessengerBot
 *
 * @package GigaAI
 */
class MessengerBot
{
    /**
     * @var MessageSender
     */
    private $messageSender;

    /**
     * @var RuleManager
     */
    private $ruleManager;

    /**
     * @var MessageResponderInterface
     */
    private $messageResponder;

    /**
     * MessengerBot2 constructor.
     * @param MessageSender $messageSender
     * @param RuleManager $ruleManager
     * @param MessageResponderInterface $messageResponder
     */
    public function __construct(
        MessageSender $messageSender,
        RuleManager $ruleManager,
        MessageResponderInterface $messageResponder
    ) {
        $this->messageSender = $messageSender;
        $this->ruleManager = $ruleManager;
        $this->messageResponder = $messageResponder;
    }

    /**
     * Check if all answer rules have been initialized or not
     *
     * @return bool
     */
    public function rulesInitialized()
    {
        return $this->ruleManager->initialized();
    }

    public function answer($request, $response)
    {
        $this->ruleManager->addRule($request, $response);

        return $this;
    }

    public function then(callable $callback)
    {
        $this->ruleManager->addThenHandler($callback);

        return $this;
    }

    /**
     * Catch incoming message fron FB and then send response message to FB
     * Log all message to storage
     */
    public function run()
    {
        $this->messageResponder->setRules($this->ruleManager->getAll());

        $incomingMessages = $this->getIncomingMessages();

        if (empty($incomingMessages)) {
            return;
        }

        foreach ($incomingMessages as $incomingMessage) {
            // Incoming message can be a message or a post back
            $input = isset($incomingMessage->message->text) ? $incomingMessage->message->text : ('payload:' . $incomingMessage->postback->payload);

            $outMessages = $this->messageResponder->response($incomingMessage->sender->id, $input);

            foreach ($outMessages as $outMessage) {
                $this->messageSender->send($outMessage);
            }
       }
    }

    /**
     * Get FB incoming messages and put them in an array
     *
     * @return array
     */
    private function getIncomingMessages()
    {
        $incomingMessages = [];

        $request = !empty($_REQUEST) ? $_REQUEST : json_decode(file_get_contents('php://input'));

        if (empty($request)) {
            return [];
        }

        foreach ($request->entry as $entry) {
            foreach ($entry->messaging as $incomingMessage) {
                // Process `message` && `postback` type only
                if (!$incomingMessage->message && !$incomingMessage->postback) {
                    continue;
                }

                $incomingMessages[] = $incomingMessage;
            }
        }

        return $incomingMessages;
    }
}