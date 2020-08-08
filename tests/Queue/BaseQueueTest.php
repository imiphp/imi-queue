<?php
namespace Imi\Queue\Test\Queue;

use Imi\Queue\Model\Message;
use Imi\Queue\Driver\IQueueDriver;
use Swoole\Coroutine\Channel;

abstract class BaseQueueTest extends BaseTest
{
    protected abstract function getDriver(): IQueueDriver;

    public function testPush()
    {
        $driver = $this->getDriver();

        $message = new Message;
        $message->setMessage('a');
        $messageId = $driver->push($message);
        $this->assertNotEmpty($messageId);
    }

    public function testPop()
    {
        $message = $this->getDriver()->pop();
        $this->assertInstanceOf(\Imi\Queue\Contract\IMessage::class, $message);
        $this->assertNotEmpty($message->getMessageId());
    }

    public function testPushDelay()
    {
        $driver = $this->getDriver();
        $driver->clear();
        $message = new Message;
        $message->setMessage('b');
        $messageId = $driver->push($message, 3);
        $this->assertNotEmpty($messageId);

        $time = microtime(true);
        for($i = 0; $i < 3; ++$i)
        {
            sleep(1);
            $message = $driver->pop();
            if(null !== $message)
            {
                break;
            }
        }
        $this->assertEquals(3, (int)(microtime(true) - $time));
        $this->assertInstanceOf(\Imi\Queue\Contract\IMessage::class, $message);
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

        $driver->fail($message);

        $this->assertEquals(1, $driver->restoreFailMessages());
        
        // requeue
        $message = $this->getDriver()->pop();
        $this->assertNotEmpty($message->getMessageId());

        $driver->fail($message, true);

        $this->assertEquals(1, $driver->status()->getReady());
        $this->assertEquals(0, $driver->restoreFailMessages());
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
