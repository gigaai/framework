<?php


namespace GigaAI\Core\User;


class RedisUserRepository implements UserRepositoryInterface
{
    private $redis;

    /**
     * RedisUserRepository constructor.
     *
     * @param $redis
     */
    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     * Set flag to indicate that $userId is waiting response for rule $ruleId
     *
     * @param $userId
     * @param $ruleId
     * @return void
     */
    public function setWait($userId, $ruleId)
    {
        $key = 'wait_' . $userId;

        $this->redis->set($key, $ruleId . "");
    }

    /**
     * Remove flag wait for $userId
     *
     * @param $userId
     */
    public function unsetWait($userId)
    {
        $key = 'wait_' . $userId;

        $this->redis->del($key);
    }

    /**
     * Check if $userId is currently wait for $ruleId
     *
     * @param $userId
     *
     * @return int|null
     */
    public function getWaitingRuleId($userId)
    {
        $key = 'wait_' . $userId;
        return $this->redis->get($key);
    }
}