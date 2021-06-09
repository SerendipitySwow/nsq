<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq\Frame;

use JetBrains\PhpStorm\Pure;
use SerendipitySwow\Nsq\Frame;

final class Message extends Frame
{
    #[Pure]
    public function __construct(
        public int $timestamp,
        public int $attempts,
        public string $id,
        public string $body,
    ) {
        parent::__construct(self::TYPE_MESSAGE);
    }
}
