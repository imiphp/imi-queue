<?php

declare(strict_types=1);

namespace Imi\Queue\Event\Listener;

use Imi\Queue\Event\Param\ConsumerBeforeConsumeParam;

/**
 * 消费者消费消息前置事件.
 */
interface IConsumerBeforeConsumeListener
{
    public function handle(ConsumerBeforeConsumeParam $e): void;
}
