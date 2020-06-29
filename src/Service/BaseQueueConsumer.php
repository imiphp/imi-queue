<?php
namespace Imi\Queue\Service;

use Swoole\Coroutine;
use Imi\Aop\Annotation\Inject;
use Yurun\Swoole\CoPool\CoPool;
use Imi\Queue\Contract\IMessage;
use Imi\Queue\Driver\IQueueDriver;
use Yurun\Swoole\CoPool\Interfaces\ICoTask;
use Yurun\Swoole\CoPool\Interfaces\ITaskParam;

/**
 * 队列消费基类
 */
abstract class BaseQueueConsumer
{
    /**
     * @Inject("imiQueue")
     *
     * @var \Imi\Queue\Service\QueueService
     */
    protected $imiQueue;

    /**
     * 队列名称
     *
     * @var string
     */
    private $name;

    /**
     * 是否正在工作
     *
     * @var bool
     */
    private $working = false;

    /**
     * 协程工作池
     *
     * @var \Yurun\Swoole\CoPool\CoPool
     */
    private $coPool;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    /**
     * 开始消费循环
     *
     * @param integer|null $co
     * @return void
     */
    public function start(?int $co = null)
    {
        $this->working = true;
        $config = $this->imiQueue->getQueueConfig($this->name);
        if(null === $co)
        {
            $co = $config->getCo();
        }
        $task = function() use($config){
            $queue = $this->imiQueue->getQueue($this->name);
            do {
                $message = $queue->pop();
                if(null === $message)
                {
                    Coroutine::sleep($config->getTimespan());
                }
                else
                {
                    $this->consume($message, $queue);
                }
            } while($this->working);  
        };
        if($co > 0)
        {
            $this->coPool = $pool = new CoPool($co, $co, new class implements ICoTask {
                /**
                 * 执行任务
                 *
                 * @param ITaskParam $param
                 * @return mixed
                 */
                public function run(ITaskParam $param)
                {
                    ($param->getData()['task'])();
                }
    
            });
            $pool->run();
            for($i = 0; $i < $co; ++$i)
            {
                $pool->addTask([
                    'task'  =>  $task,
                ]);
            }
            while($pool->isRunning())
            {
                Coroutine::sleep(0.1);
            }
        }
        else
        {
            ($task)();
        }
    }

    /**
     * 停止消费
     *
     * @return void
     */
    public function stop()
    {
        $this->working = false;
        if($this->coPool)
        {
            $this->coPool->stop();
        }
    }

    /**
     * 处理消费
     * 
     * @param \Imi\Queue\Contract\IMessage $message
     * @param \Imi\Queue\Driver\IQueueDriver $queue
     * @return void
     */
    protected abstract function consume(IMessage $message, IQueueDriver $queue);

}
