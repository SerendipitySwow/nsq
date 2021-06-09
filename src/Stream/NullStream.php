<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq\Stream;

use Amp\Promise;
use Amp\Success;
use SerendipitySwow\Nsq\Stream;
use function Amp\call;

final class NullStream implements Stream
{
    /**
     * {@inheritdoc}
     */
    public function read()
    {
        return new Success(null);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data)
    {
        return call(static function () : void
        {
        });
    }

    /**
     * {@inheritdoc}
     */
    public function close() : void
    {
    }
}
