<?php
namespace Imi\Queue\Test;

use Imi\App;
use Imi\Queue\Model\Message;
use Imi\Queue\Driver\IQueueDriver;
use Imi\Queue\Driver\RedisQueueDriver;

class QueueTest extends BaseTest
{
    protected function getDriver(): IQueueDriver
    {
        return App::getBean(RedisQueueDriver::class, 'imi-queue-test');
    }

    public function testPush()
    {
        $driver = $this->getDriver();

        $message = new Message;
        $message->setMessage('a');
        $messageId = $driver->push($message);
        $this->assertNotEmpty($messageId);

        $message->setMessage('b');
        $messageId = $driver->push($message, 3600);
        $this->assertNotEmpty($messageId);
    }

    public function testPop()
    {
        $message = $this->getDriver()->pop();
        $this->assertNotEmpty($message->getMessageId());
    }

    public function testDelete()
    {
        $driver = $this->getDriver();

        $message = new Message;
        $message->setMessage('a');
        $messageId = $driver->push($message);
        $this->assertNotEmpty($messageId);

        $message->setMessageId($messageId);

        $this->assertTrue($driver->delete($message));
    }

    public function testClearAndStatus()
    {
        $driver = $this->getDriver();
        $driver->clear();
        $status = $driver->status();
        $this->assertEquals(0, $status->getReady());
        $this->assertEquals(0, $status->getWorking());
        $this->assertEquals(0, $status->getDelay());
        $this->assertEquals(0, $status->getTimeout());
        $this->assertEquals(0, $status->getFail());

        $message = new Message;
        $message->setMessage('a');
        $messageId = $driver->push($message);
        $this->assertNotEmpty($messageId);

        $message->setMessage('b');
        $messageId = $driver->push($message, 3600);
        $this->assertNotEmpty($messageId);

        $status = $driver->status();
        $this->assertEquals(1, $status->getReady());
        $this->assertEquals(1, $status->getDelay());

    }

    public function testRestoreFailMessages()
    {
        $driver = $this->getDriver();
        $driver->clear();

        $message = new Message;
        $message->setMessage('a');
        $messageId = $driver->push($message);
        $this->assertNotEmpty($messageId);

        $message = $this->getDriver()->pop();
        $this->assertNotEmpty($message->getMessageId());

        $driver->fail($message, 'gg');

        $this->assertEquals(1, $driver->restoreFailMessages());
    }

    public function testRestoreTimeoutMessages()
    {
        $driver = $this->getDriver();
        $driver->clear();

        $message = new Message;
        $message->setMessage('a');
        $message->setWorkingTimeout(1);
        $messageId = $driver->push($message);
        $this->assertNotEmpty($messageId);

        $message = $this->getDriver()->pop();
        $this->assertNotEmpty($message->getMessageId());

        sleep(1);

        $message = $this->getDriver()->pop();
        $this->assertNull($message);

        $this->assertEquals(1, $driver->restoreTimeoutMessages());
    }

}
