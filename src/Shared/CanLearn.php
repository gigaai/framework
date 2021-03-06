<?php

namespace GigaAI\Shared;

use GigaAI\Conversation\Conversation;
use GigaAI\Storage\Eloquent\Lead;
use GigaAI\Http\HandoverProtocol;

trait CanLearn
{
    /**
     * Response user question with answers
     *
     * @param      $ask
     * @param null $answers
     *
     * @return $this
     */
    public function answer($ask, $answers = null, $attributes = [])
    {
        return $this->answers($ask, $answers, $attributes);
    }

    /**
     * Format answer from short hand to proper form.
     *
     * @param      $asks
     * @param null $answers
     *
     * @return $this For chaining method
     */
    public function answers($asks, $answers = null, $attributes = [])
    {
        $this->model->addNode($asks, $answers, $attributes);

        return $this;
    }

    /**
     * Alias of says() method
     *
     * @param mixed $messages Message to send
     * @param array $attributes Message attributes
     * @param       $lead_ids Lead ID to send, by default current lead
     *
     * @return $this
     */
    public function say($messages, $attributes = [], $lead_ids = null)
    {
        return $this->says($messages, $attributes, $lead_ids);
    }

    /**
     * Send message to user.
     *
     * @param mixed $messages Message to send
     * @param array $attributes Message attributes
     * @param       $lead_ids Lead ID to send, by default current lead
     *
     * @return $this
     */
    public function says($messages, $attributes = [], $lead_ids = null)
    {
        $messages = $this->model->parse($messages);

        if ( ! is_null($lead_ids)) {
            $lead_ids = (array) $lead_ids;
            $leads = Lead::withTrashed()->whereIn('user_id', $lead_ids)->get();

            if (empty($leads) || $leads === null) {
                return;
            }

            $leads->each(function ($lead) use ($messages, $attributes) {
                $this->request->sendMessages($messages, $attributes, $lead);
            });
        } else {
            $this->request->sendMessages($messages, $attributes, $lead_ids);
        }

        return $this;
    }

    /**
     * Named Intended Action
     *
     * @param $action
     */
    public function wait($action)
    {
        $lead = $this->conversation->get('lead');

        // For chaining after $bot->say() method
        if ($lead != null) {
            $lead->data('_wait', $action);
        } // For chaining after $bot->answer() method
        else {
            $this->model->addIntendedAction($action);
        }
    }

    /**
     * Index Intended Action
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function then(callable $callback)
    {
        $this->model->addIntendedAction($callback);

        return $this;
    }

    /**
     * Keep staying in current intended action.
     *
     * @param $messages
     */
    public function keep($messages)
    {
        $previous_intended_action = $this->conversation->get('previous_intended_action');

        if ($previous_intended_action == null) {
            return;
        }

        $this->says($messages)->wait($previous_intended_action);
    }

    public function release()
    {
        $this->run();
    }

    public function parse($answers, $attributes = [])
    {
        return $this->model->parse($answers, $attributes);
    }

    public function understand($pattern)
    {
        //
    }

    public function doesntUnderstand($pattern)
    {
        return ! $this->understand($pattern);
    }

    /**
     * Check current message is text
     *
     * @return bool
     */
    public function isText()
    {
        return ( ! empty($this->message));
    }

    /**
     * Check current message is postback
     *
     * @return bool
     */
    public function isPostback()
    {
        return ( ! empty($this->postback));
    }

    /**
     * Send typing indicator
     *
     * @param int $delay Typing in seconds
     *
     * @return void
     */
    public function typing($delay = 2)
    {
        $this->request->sendTyping();
        sleep($delay);

        return $this;
    }

    /**
     * Natural language processing
     * 
     * @return String
     */
    public function nlp($entity = null)
    {
        return $this->nlp->filter($entity);
    }

    /**
     * Let the Page Inbox app handle the message instead of bot
     * 
     * @return Json
     */
    public function passToInbox()
    {
        $handover = new HandoverProtocol;
        
        return $handover->passToInbox();
    }

    /**
     * Simulate the user input and response
     */
    public function simulate($input)
    {
        // Todo: Add simulate method to make app more easy to test
    }
}
