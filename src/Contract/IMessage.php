<?php
namespace Imi\Queue\Contract;

use Imi\Util\Interfaces\IArrayable;

/**
 * 消息接口
 */
interface IMessage extends IArrayable
{
    /**
     * 获取消息 ID
     *
     * @return string
     */
    public function getMessageId(): string;

    /**
     * 设置消息 ID
     *
     * @param string $messageId
     * @return void
     */
    public function setMessageId(string $messageId);

    /**
     * 获取消息内容
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * 设置消息内容
     *
     * @param string $message
     * @return void
     */
    public function setMessage(string $message);

    /**
     * 获取工作超时时间，单位：秒
     *
     * @return float
     */
    public function getWorkingTimeout(): float;

    /**
     * 设置工作超时时间，单位：秒
     *
     * @param float $workingTimeout
     * @return void
     */
    public function setWorkingTimeout(float $workingTimeout);

    /**
     * 从数组加载数据
     *
     * @param array $data
     * @return void
     */
    public function loadFromArray(array $data);

}
