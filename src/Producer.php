<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq;

use SerendipitySwow\Nsq\Config\ClientConfig;
use SerendipitySwow\Nsq\Exception\NsqException;
use SerendipitySwow\Nsq\Stream\NullStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Amp\asyncCall;
use function Amp\call;

final class Producer extends Connection
{
    public static function create(
        string $address,
        ClientConfig $clientConfig = null,
        LoggerInterface $logger = null,
    ) : self {
        return new self(
            $address,
            $clientConfig ?? new ClientConfig(),
            $logger ?? new NullLogger(),
        );
    }

    public function connect()
    {
        if (!$this->stream instanceof NullStream) {
            return call(static function () : void
            {
            });
        }

        return call(function () : \Generator
        {
            yield parent::connect();

            $this->run();
        });
    }

    /**
     * @param array<int, string>|string $body
     *
     * @psalm-param positive-int|0      $delay
     *
     * @return <void>
     */
    public function publish(string $topic, string|array $body, int $delay = 0)
    {
        if (0 < $delay) {
            return call(
                function (array $bodies) use ($topic, $delay) : \Generator
                {
                    foreach ($bodies as $body) {
                        yield $this->stream->write(Command::dpub($topic, $body, $delay));
                    }
                },
                (array)$body,
            );
        }

        $command = \is_array($body)
            ? Command::mpub($topic, $body)
            : Command::pub($topic, $body);

        return $this->stream->write($command);
    }

    private function run() : void
    {
        $buffer = new Buffer();

        asyncCall(function () use ($buffer) : \Generator
        {
            while (null !== $chunk = yield $this->stream->read()) {
                $buffer->append($chunk);

                while ($frame = Parser::parse($buffer)) {
                    switch (true) {
                        case $frame instanceof Frame\Response:
                            if ($frame->isHeartBeat()) {
                                yield $this->stream->write(Command::nop());
                            }

                            // Ok received
                            break;
                        case $frame instanceof Frame\Error:
                            $this->handleError($frame);

                            break;
                        default:
                            throw new NsqException('Unreachable statement.');
                    }
                }
            }

            $this->stream = new NullStream();
        });
    }
}
