<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq\Frame;

use JetBrains\PhpStorm\Pure;
use SerendipitySwow\Nsq\Exception\ServerException;
use SerendipitySwow\Nsq\Frame;

/**
 * @psalm-immutable
 */
final class Error extends Frame
{
    #[Pure]
    public function __construct(
        public string $data
    ) {
        parent::__construct(self::TYPE_ERROR);
    }

    #[Pure]
    public function toException() : ServerException
    {
        return new ServerException($this->data);
    }
}
