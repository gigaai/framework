<?php


namespace GigaAI\Core\User;


interface UserRepositoryInterface
{
    /**
     * Set flag to indicate that $userId is waiting response for rule $ruleId
     *
     * @param $userId
     * @param $ruleId
     * @return void
     */
    public function setWait($userId, $ruleId);

    /**
     * Remove flag wait for $userId
     *
     * @param $userId
     */
    public function unsetWait($userId);

    /**
     * Check if $userId is currently wait for $ruleId
     *
     * @param $userId
     *
     * @return int|null
     */
    public function getWaitingRuleId($userId);
}