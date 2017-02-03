<?php
class RedisCache {

    protected static $instance;

    public static function getInstance($redis_database = 0){

        if (static::$instance) {
            static::$instance->select($redis_database);
        } else {
            static::$instance = new \Redis();
            static::$instance->connect(REDIS_IP, REDIS_PORT);
            static::$instance->select($redis_database);
        }

        return static::$instance;

    }

}