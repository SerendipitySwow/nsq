<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq;

use Amp\Promise;
use SerendipitySwow\Nsq\Config\ClientConfig;
use SerendipitySwow\Nsq\Config\ServerConfig;
use SerendipitySwow\Nsq\Exception\AuthenticationRequired;
use SerendipitySwow\Nsq\Exception\NsqException;
use SerendipitySwow\Nsq\Frame\Response;
use SerendipitySwow\Nsq\Stream\GzipStream;
use SerendipitySwow\Nsq\Stream\NullStream;
use SerendipitySwow\Nsq\Stream\SnappyStream;
use SerendipitySwow\Nsq\Stream\SocketStream;
use Psr\Log\LoggerInterface;
use function Amp\call;

/**
 * @internal
 */
abstract class Connection
{
    protected Stream $stream;

    public function __construct(
        private string $address,
        private ClientConfig $clientConfig,
        private LoggerInterface $logger,
    ) {
        $this->stream = new NullStream();
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return Promise<void>
     */
    public function connect() : Promise
    {
        return call(function () : \Generator
        {
            $buffer = new Buffer();

            /** @var SocketStream $stream */
            $stream = yield SocketStream::connect(
                $this->address,
                $this->clientConfig->connectTimeout,
                $this->clientConfig->maxAttempts,
                $this->clientConfig->tcpNoDelay,
            );

            yield $stream->write(Command::magic());
            yield $stream->write(Command::identify($this->clientConfig->asNegotiationPayload()));

            /** @var Response $response */
            $response     = yield $this->response($stream, $buffer);
            $serverConfig = ServerConfig::fromArray($response->toArray());

            if ($serverConfig->snappy) {
                $stream = new SnappyStream($stream, $buffer->flush());

                /** @var Response $response */
                $response = yield $this->response($stream, $buffer);

                if (!$response->isOk()) {
                    throw new NsqException();
                }
            }

            if ($serverConfig->deflate) {
                $stream = new GzipStream($stream);

                /** @var Response $response */
                $response = yield $this->response($stream, $buffer);

                if (!$response->isOk()) {
                    throw new NsqException();
                }
            }

            if ($serverConfig->authRequired) {
                if (null === $this->clientConfig->authSecret) {
                    throw new AuthenticationRequired();
                }

                yield $stream->write(Command::auth($this->clientConfig->authSecret));

                /** @var Response $response */
                $response = yield $this->response($stream, $buffer);

                $this->logger->info('Authorization response: ' . http_build_query($response->toArray()));
            }

            $this->stream = $stream;
        });
    }

    public function close() : void
    {
        //        $this->stream->write(Command::cls());

        $this->stream->close();
        $this->stream = new NullStream();
    }

    protected function handleError(Frame\Error $error) : void
    {
        $this->logger->error($error->data);

        if (ErrorType::terminable($error)) {
            $this->close();

            throw $error->toException();
        }
    }

    /**
     * @return Promise<Frame\Response>
     */
    private function response(Stream $stream, Buffer $buffer) : Promise
    {
        return call(function () use ($stream, $buffer) : \Generator
        {
            while (true) {
                $response = Parser::parse($buffer);

                if (null === $response && null !== ($chunk = yield $stream->read())) {
                    $buffer->append($chunk);

                    continue;
                }

                if (!$response instanceof Frame\Response) {
                    throw new NsqException();
                }

                return $response;
            }
        });
    }
}
