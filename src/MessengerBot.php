<?php


namespace GigaAI;


use GigaAI\Core\MessageSender;
use GigaAI\Core\Responders\MessageResponderInterface;
use GigaAI\Core\Rule\RuleManager;
use GigaAI\Messages\TextMessage;

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

    public function say($response)
    {
        echo $response;
        // Response to FB a new TextMessage with content $response
//        $message = new TextMessage()
    }

    /**
     * Catch incoming message fron FB and then send response message to FB
     * Log all message to storage
     */
    public function run()
    {
        $incomingMessages = $this->getIncomingMessages();

        if (empty($incomingMessages)) {
            return;
        }

        foreach ($incomingMessages as $incomingMessage) {
            list($outMessages, $isWait) = $this->messageResponder->response($incomingMessage->sender->id, $incomingMessage->message->text);

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
                // Process `message` type only
                if (!$incomingMessage->message) {
                    continue;
                }

                $incomingMessages[] = $incomingMessage;
            }
        }

        return $incomingMessages;
    }
}