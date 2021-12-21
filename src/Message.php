<?php
/**
 * This file is part of Swow
 * @license  https://github.com/swow-cloud/nsq/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Nsq;

class Message
{
    /**
     * @var string nanosecond
     */
    protected string $timestamp;

    /**
     * @var int
     */
    protected mixed $attempts;

    protected string $messageId;

    protected string $body;

    public function __construct(string $payload)
    {
        // u16int to u32int
        $left = sprintf('%u', Packer::unpackUInt32(substr($payload, 0, 4))[1]);
        $right = sprintf('%u', Packer::unpackUInt32(substr($payload, 4, 4))[1]);
        $this->timestamp = bcadd(bcmul($left, '4294967296'), $right);
        $this->attempts = Packer::unpackUInt16(substr($payload, 8, 2));
        $this->messageId = Packer::unpackString(substr($payload, 10, 16));
        $this->body = Packer::unpackString(substr($payload, 26));
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function setTimestamp($timestamp): Message
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getAttempts()
    {
        return $this->attempts;
    }

    public function setAttempts($attempts): Message
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): Message
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): Message
    {
        $this->body = $body;

        return $this;
    }
}
