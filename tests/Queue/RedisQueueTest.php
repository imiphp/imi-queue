<?php
namespace Imi\Queue\Test\Queue;

use Imi\App;
use Imi\Queue\Driver\IQueueDriver;
use Imi\Queue\Driver\RedisQueueDriver;

class RedisQueueTest extends BaseQueueTest
{
    protected function getDriver(): IQueueDriver
    {
        return App::getBean(RedisQueueDriver::class, 'imi-queue-test');
    }
}
