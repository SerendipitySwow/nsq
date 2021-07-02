<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SerendipitySwow\Nsq;

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
