<?php

declare(strict_types=1);

namespace Imi\Queue\Process;

use Imi\Aop\Annotation\Inject;
use Imi\App;
use Imi\Event\Event as ImiEvent;
use Imi\Log\Log;
use Imi\Queue\Service\QueueService;
use Imi\Swoole\Process\Annotation\Process;
use Imi\Swoole\Process\BaseProcess;
use Imi\Swoole\Util\Imi;
use Swoole\Coroutine;
use Swoole\Event;

if (\Imi\Util\Imi::checkAppType('swoole'))
{
    /**
     * Swoole 队列消费进程.
     *
     * @Process(name="QueueConsumer", unique=true, co=false)
     */
    class SwooleQueueConsumerProcess extends BaseProcess
    {
        /**
         * @Inject("imiQueue")
         */
        protected QueueService $imiQueue;

        /**
         * 消费者列表.
         *
         * @var \Imi\Queue\Service\BaseQueueConsumer[]
         */
        private array $consumers = [];

        public function run(\Swoole\Process $process): void
        {
            $imiQueue = $this->imiQueue;
            $processGroups = [];
            foreach ($imiQueue->getList() as $name => $arrayConfig)
            {
                $config = $imiQueue->getQueueConfig($name);
                if (!$config->getAutoConsumer())
                {
                    continue;
                }
                $group = $config->getProcessGroup();
                $process = $config->getProcess();
                if (!isset($processGroups[$group]) || $process > $processGroups[$group]['process'])
                {
                    $processGroups[$group]['process'] = $process;
                }
                $processGroups[$group]['configs'][] = $config;
            }
            foreach ($processGroups as $group => $options)
            {
                $processPool = new \Imi\Swoole\Process\Pool($options['process']);
                $configs = $options['configs'];
                $processPool->on('WorkerStart', function (\Imi\Swoole\Process\Pool\WorkerEventParam $e) use ($group, $configs) {
                    Coroutine::create(function () use ($group, $configs) {
                        $processName = 'QueueConsumer-' . $group;
                        // 进程开始事件
                        ImiEvent::trigger('IMI.PROCESS.BEGIN', [
                            'name'      => $processName,
                            'process'   => $e->getWorker(),
                        ]);
                        \Swoole\Runtime::enableCoroutine(true);
                        Imi::setProcessName('process', [
                            'processName'   => $processName,
                        ]);
                        /** @var \Imi\Queue\Model\QueueConfig[] $configs */
                        foreach ($configs as $config)
                        {
                            Coroutine::create(function () use ($config) {
                                /** @var \Imi\Queue\Service\BaseQueueConsumer $queueConsumer */
                                $queueConsumer = $this->consumers[] = App::getBean($config->getConsumer(), $config->getName());
                                $queueConsumer->start();
                            });
                        }
                    });
                });
                // 工作进程退出事件-可选
                $processPool->on('WorkerExit', function (\Imi\Swoole\Process\Pool\WorkerEventParam $e) use ($group) {
                    // 做一些释放操作
                    foreach ($this->consumers as $consumer)
                    {
                        $consumer->stop();
                    }
                    // 进程结束事件
                    ImiEvent::trigger('IMI.PROCESS.END', [
                        'name'      => 'QueueConsumer-' . $group,
                        'process'   => $e->getWorker(),
                    ]);
                });
                $processPool->start();
            }
            if (!isset($name))
            {
                Log::warning('@app.beans.imiQueue.list is empty');
                // @phpstan-ignore-next-line
                while (true)
                {
                    sleep(86400);
                }
            }
            Event::wait();
        }
    }
}
