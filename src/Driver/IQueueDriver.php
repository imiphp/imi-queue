<?php

declare(strict_types=1);

namespace Imi\Queue\Driver;

use Imi\Queue\Contract\IMessage;
use Imi\Queue\Enum\IQueueType;
use Imi\Queue\Model\QueueStatus;

/**
 * 队列驱动接口.
 */
interface IQueueDriver
{
    /**
     * 获取队列名称.
     */
    public function getName(): string;

    /**
     * 推送消息到队列，返回消息ID.
     */
    public function push(IMessage $message, float $delay = 0, array $options = []): string;

    /**
     * 从队列弹出一个消息.
     *
     * @param float $timeout 超时时间，单位：秒。值小于等于0时立即返回结果
     */
    public function pop(float $timeout = 0): ?IMessage;

    /**
     * 删除一个消息.
     */
    public function delete(IMessage $message): bool;

    /**
     * 清空队列.
     */
    public function clear(?IQueueType $queueType = null): void;

    /**
     * 将消息标记为成功
     */
    public function success(IMessage $message): int;

    /**
     * 将消息标记为失败.
     */
    public function fail(IMessage $message, bool $requeue = false): int;

    /**
     * 获取队列状态
     */
    public function status(): QueueStatus;

    /**
     * 将失败消息恢复到队列.
     *
     * 返回恢复数量
     */
    public function restoreFailMessages(): int;

    /**
     * 将超时消息恢复到队列.
     *
     * 返回恢复数量
     */
    public function restoreTimeoutMessages(): int;
}
