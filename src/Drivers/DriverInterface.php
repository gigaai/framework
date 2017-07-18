<?php

namespace GigaAI\Drivers;

interface DriverInterface
{
    public function exptectedFormat($request);

    public function formatIncomingRequest($request);

    public function formatOutcomingRequest($request);
}