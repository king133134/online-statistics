<?php

class OnlineStatistics
{

    protected $redis;

    protected $pre_key = 'online_'; //redis統計key的前綴，用來區分不同的平台統計

    protected $redis_database;

    protected $interval_min; //統計的時間區間

    protected $timestamp;

    public function __construct($redis_database = 0, $interval_min = 30)
    {

        $this->redis = RedisCache::getInstance($redis_database);

        $this->redis_database = $redis_database;
        $this->interval_min   = $interval_min;

        $this->timestamp = $_SERVER['REQUEST_TIME'];

    }

    public function run($uniqid)
    {

        $this->insert($uniqid);

        return $this->getCount();

    }

    public function getCount()
    {

        $keys = $this->getValidKeys();

        $per   = ($this->timestamp % 60) / 60;
        $count = 0;

        $first_key       = $keys[0];
        $first_key_count = $this->redis->sCard($first_key);
        if ($first_key_count) {
            $first_key = array_shift($keys);
            $count     = (int) ($first_key_count * (1 - $per));
        }

        foreach ($keys as $key) {
            $count += $this->redis->sCard($key);
        }

        return $count;

    }

    public function insert($uniqid)
    {

        $keys = $this->getValidKeys();

        $now_key = array_pop($keys);

        $exists = false;
        foreach ($keys as $key) {
            $exists = $this->redis->sIsMember($key, $uniqid);
            if ($exists) {
                break;
            }
        }

        if ($exists) {
            $res = $this->redis->sMove($key, $now_key, $uniqid);
        } else {
            $res = $this->redis->sAdd($now_key, $uniqid);
        }

        $this->setExpire($now_key);

        return $res;

    }

    public function getList()
    {

        $keys = $this->getValidKeys();
        $list = array();

        foreach ($keys as $key) {
            $list[$key] = $this->redis->sCard($key);
        }

        return $list;

    }

    private function setExpire($key)
    {

        if ($this->redis->ttl($key) == -1) {
            $this->redis->setTimeout($key, ($this->interval_min + 2) * 60);
        }

    }

    private function getRedisDatabase()
    {

        return $this->redis_database;

    }

    public function getValidKeys()
    {

        $start_time       = date('H_i', $this->timestamp - $this->interval_min * 60);
        list($hour, $min) = explode('_', $start_time);
        $hour             = (int) $hour;
        $min              = (int) $min;
        $keys             = array();

        $interval_min = $this->interval_min + 1;
        for ($i = 0; $i < $interval_min; ++$i) {
            if ($i > 0) {
                ++$min;
            }

            if ($min == 60) {
                $min = 0;
                ++$hour;
                if ($hour == 24) {
                    $hour = 0;
                }
            }
            $keys[] = $this->pre_key . $this->num2str($hour) . $this->num2str($min);
        }

        return $keys;

    }

    public function getInValidKey()
    {

        return $this->pre_key . date('Hi', $this->timestamp - ($this->interval_min + 1) * 60);

    }

    private function num2str($num)
    {
        return $num > 9 ? $num : '0' . $num;
    }

}
