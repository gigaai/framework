<?php

namespace GigaAI\Shared;

trait CanLearn
{
    /**
     * Response user question with answers
     *
     * @param $ask
     * @param null $answers
     *
     * @return $this
     */
    public function answer($ask, $answers = null)
    {
        return $this->answers($ask, $answers);
    }

    /**
     * Format answer from short hand to proper form.
     *
     * @param $asks
     * @param null $answers
     *
     * @return $this For chaining method
     */
    public function answers($asks, $answers = null)
    {
        $this->model->addNode($asks, $answers);

        return $this;
    }

    /**
     * Alias of says() method
     *
     * @param $messages
     * @return $this
     */
    public function say($messages)
    {
        return $this->says($messages);
    }

    /**
     * Send message to user.
     *
     * @param $messages
     * @return $this
     */
    public function says($messages)
    {
        $messages = $this->model->parseWithoutSave($messages);

        $this->request->sendMessages($messages);

        return $this;
    }

    /**
     * Named Intended Action
     *
     * @param $action
     */
    public function wait($action)
    {
        $lead_id = $this->conversation->get('lead_id');

        // For chaining after $bot->say() method
        if ($lead_id != null)
            $this->storage->set($lead_id, '_wait', $action);

        // For chaining after $bot->answer() method
        else
            $this->model->addIntendedAction($action);
    }

    /**
     * Index Intended Action
     *
     * @param callable $callback
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

        if ($previous_intended_action == null)
            return;

        $this->says($messages)->wait($previous_intended_action);
    }
    
    public function release()
    {
        $this->run();
    }

    public function understand($pattern)
    {
        
    }

    public function doesntUnderstand($pattern)
    {
        return ! $this->understand($pattern);
    }
}

