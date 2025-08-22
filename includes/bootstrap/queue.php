<?php

declare(strict_types=1);

use sigawa\mvccore\Application;
use sigawa\mvccore\que\DatabaseQueue;
use sigawa\mvccore\que\JobSerializer;
use sigawa\mvccore\que\RedisQueue;


$serializer = new JobSerializer();

$pdo = Application::$app->db->pdo;
$queue = new DatabaseQueue($pdo);

return [$queue, $serializer];
