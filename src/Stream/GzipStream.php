<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq\Stream;

use Amp\Promise;
use SerendipitySwow\Nsq\Exception\NsqException;
use SerendipitySwow\Nsq\Stream;

class GzipStream implements Stream
{
    public function __construct(private Stream $stream)
    {
        throw new NsqException('GzipStream not implemented yet.');
    }

    public function read()
    {
        return $this->stream->read();
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data)
    {
        return $this->stream->write($data);
    }

    public function close() : void
    {
        $this->stream->close();
    }
}
