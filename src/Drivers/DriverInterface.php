<?php

namespace GigaAI\Drivers;

interface DriverInterface
{
    /**
     * Expected current request is of driver or not
     *
     * @param Array $request
     * 
     * @return bool
     */
    public function exptectedFormat($request);

    /**
     * Convert incoming request to Facebook format
     *
     * @param Array $request
     * @return void
     */
    public function formatIncomingRequest($request);

    /**
     * Send message back to the server
     *
     * @param Array $request
     * @return void
     */
    public function sendMessage($request);

    /**
     * Get the current user info to pass to the DB
     *
     * @param String $lead_id
     * @return void
     */
    public function getUser($lead_id);
}