<?php


namespace GigaAI;


use GigaAI\Core\Config;
use GigaAI\Core\MessageSender;
use GigaAI\Core\Responders\DefaultMessageResponder;
use GigaAI\Core\Responders\MessageResponderInterface;
use GigaAI\Core\Rule\DbRuleRepository;
use GigaAI\Core\Rule\Rule;
use GigaAI\Core\Rule\RuleManager;
use GigaAI\Core\User\RedisUserRepository;
use GigaAI\Http\HttpClient;
use SuperClosure\Serializer;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class MessengerBot
 *
 * @package GigaAI
 */
class MessengerBot
{
    private static $instance = null;

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

        //TODO refactor this
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'gigaai',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => ''
        ]);

        $capsule->bootEloquent();
    }

    /**
     * Return MessengerBot instance
     *
     * @return MessengerBot|null
     */
    public static function getInstance()
    {
        //TODO all of these should be injected through DI & config system
        if (self::$instance === null) {
            $accessToken = Config::get('page_access_token');
            $messageSender = new MessageSender(new HttpClient(), $accessToken);

            $redis = new \Predis\Client(Config::get('redis'));
            $ruleRepository = new DbRuleRepository(new Rule());
            $serializer = new Serializer();
            $ruleManager = new RuleManager($ruleRepository, $serializer);

            $userRepository = new RedisUserRepository($redis);
            $messageResponder = new DefaultMessageResponder($userRepository, $ruleManager);

            self::$instance = new MessengerBot($messageSender, $ruleManager, $messageResponder);


        }

        return self::$instance;
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

    /**
     * Setup answer/response rule
     *
     * @param $request
     * @param $response
     *
     * @return $this
     */
    public function answer($request, $response)
    {
        $this->ruleManager->addRule($request, $response);

        return $this;
    }

    /**
     * Add thenHandler for current rule
     *
     * @param callable $callback
     *
     * @return $this
     */
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

            $this->send($outMessages);
       }
    }

    /**
     * Response a message to user and then continue waiting user's response
     *
     * @param $responseRule
     */
    public function fail($responseRule)
    {
        $failMessages = $this->messageResponder->responseFail($responseRule);

        $this->send($failMessages);
    }

    /**
     * Send outgoing messages
     *
     * @param $outMessages
     */
    private function send($outMessages)
    {
        foreach ($outMessages as $outMessage) {
            $this->messageSender->send($outMessage);
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
                if (empty($incomingMessage->message) && empty($incomingMessage->postback)) {
                    continue;
                }

                $incomingMessages[] = $incomingMessage;
            }
        }

        return $incomingMessages;
    }
}