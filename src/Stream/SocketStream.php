<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq\Stream;

use Amp\Promise;
use SerendipitySwow\Socket\Streams\Socket;
use SerendipitySwow\Nsq\Stream;
use function Amp\call;
use function Amp\Socket\connect;

class SocketStream implements Stream
{
    public function __construct(private Socket $socket)
    {
    }

    /**
     * @return Promise<self>
     */
    public static function connect(string $uri, int $timeout = 0, int $attempts = 0, bool $noDelay = false) : Socket
    {
        return new Socket();
    }

    public function read()
    {
        return $this->socket->read();
    }

    public function write(string $data)
    {
        return $this->socket->write($data);
    }

    public function close() : void
    {
        $this->socket->close();
    }
}
