<?php


namespace GigaAI\Core\Rule;


/**
 * Class Rule
 *
 * @package GigaAI\Core
 */
class Rule
{
    public $id;

    public $request;

    public $response;

    public $thenHandler;

    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}