<?php
/**
 * This file is part of Swow
 * @license  https://github.com/swow-cloud/nsq/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Nsq;

class Result
{
    /**
     * Acknowledge the message.
     */
    public const ACK = 'ack';

    /**
     * Reject the message and requeue it.
     */
    public const REQUEUE = 'requeue';

    /**
     * Reject the message and drop it.
     */
    public const DROP = 'drop';
}
