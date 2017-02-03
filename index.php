<?php
define('REDIS_IP', '127.0.0.1');
define('REDIS_PORT', 6379);
require 'OnlineStatistics.php';
require 'RedisCache.php';

$online = new OnlineStatistics(0, 10);
$uniqid = session_id();//根據session id來統計在線人數
echo '在線人數：',$online->run($uniqid);