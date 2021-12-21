<?php
/**
 * This file is part of Swow
 * @license  https://github.com/swow-cloud/nsq/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Nsq;

use Closure;
use Hyperf\Utils\Arr;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerInterface;
use SwowCloud\Job\Logger\Logger;
use SwowCloud\Job\Logger\LoggerFactory;
use Swow\Channel;
use Swow\Coroutine;
use SwowCloud\Nsq\Exceptions\ConnectionException;
use SwowCloud\Nsq\Exceptions\SocketPopException;
use SwowCloud\Nsq\Interface\ConnectionInterface;
use SwowCloud\Socket\Exceptions\OpenStreamException;
use SwowCloud\Socket\Exceptions\StreamStateException;
use SwowCloud\Socket\Streams\Socket;

class Connection implements ConnectionInterface
{
    protected ContainerInterface $container;

    protected Channel $channel;

    protected float $lastUseTime = 0.0;

    protected bool $connected = false;

    protected string $name = 'nsq.connection';

    protected array $config = [];

    protected Logger $logger;

    public function __construct(ContainerInterface $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config ?? throw new InvalidArgumentException('Nsq Config is null#');
        $this->builder = $container->get(MessageBuilder::class);
        $this->logger = $this->container->get(LoggerFactory::class)
            ->get() ?? throw new InvalidArgumentException('Logger is Unknow#');
    }

    public function reconnect(): bool
    {
        $this->close();

        $connection = $this->getActiveConnection();

        $channel = new Channel(1);
        $channel->push($connection);
        $this->channel = $channel;
        $this->lastUseTime = microtime(true);

        $this->addHeartbeat();

        return true;
    }

    #[Pure]
    public function check(): bool
    {
        return $this->isConnected();
    }

    public function close(): bool
    {
        if ($this->isConnected()) {
            $this->call(function ($connection) {
                try {
                    if ($this->isConnected()) {
                        $this->sendClose($connection);
                    }
                } finally {
                    $this->clear();
                }
            }, false);
        }

        return true;
    }

    public function release(): void
    {
    }

    protected function clear(): void
    {
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function __destruct()
    {
        $this->clear();
    }

    public function isTimeout(): bool
    {
        return $this->lastUseTime < microtime(true) - $this->config['max_idle_time'] &&
            $this->channel->getLength() > 0;
    }

    /**
     * @param bool $refresh refresh last use time or not
     */
    public function call(Closure $closure, bool $refresh = true): mixed
    {
        if (!$this->isConnected()) {
            $this->reconnect();
        }

        $connection = $this->channel->pop($this->config['wait_timeout']);
        if ($connection === false) {
            throw new SocketPopException(sprintf(
                'Socket of %s is exhausted. Cannot establish socket before timeout.',
                $this->name
            ));
        }

        try {
            $result = $closure($connection);
            if ($refresh) {
                $this->lastUseTime = microtime(true);
            }
        } finally {
            if ($this->isConnected()) {
                $this->channel->push($connection);
            } else {
                // Unset and drop the connection.
                unset($connection);
            }
        }

        return $result;
    }

    protected function sendClose(Socket $connection): void
    {
        try {
            $connection->write($this->builder->buildCls());
        } catch (\Throwable $throwable) {
            // Do nothing
        }
    }

    protected function getActiveConnection(): Socket
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $connectionTimeout = $this->config['connect_timeout'];
        $socket = new Socket($host, $port, $connectionTimeout);
        try {
            $socket->open();
        } catch (StreamStateException | OpenStreamException $exception) {
            throw new ConnectionException($exception->getMessage(), $exception->getCode());
        }

        $socket->write($this->builder->buildMagic());
        $socket->write($this->builder->buildIdentify(['max_msg_timeout' => $this->config['max_msg_timeout'] ?? 60]));

        $reader = new Subscriber($socket);
        $reader->recv();
        if (!$reader->isOk()) {
            $result = $reader->getJsonPayload();
            if (Arr::get($result, 'auth_required') === true) {
                $socket->write($this->builder->buildAuth($this->config['auth']));

                $reader = new Subscriber($socket);
                $reader->recv();
            }
        }

        return $socket;
    }

    protected function addHeartbeat(): void
    {
        $this->connected = true;
        Coroutine::run(function () {
            try {
                if (!$this->isConnected()) {
                    return;
                }

                if ($this->isTimeout()) {
                    // The socket does not used in double of heartbeat.
                    $this->close();

                    return;
                }

                $this->heartbeat();
                usleep($this->getHeartbeat());
            } catch (\Throwable $throwable) {
                $this->clear();
                if ($logger = $this->logger) {
                    $message = sprintf('Socket of %s heartbeat failed, %s', $this->name, (string) $throwable);
                    $logger->error($message);
                }
            }
        });
    }

    protected function heartbeat(): void
    {
    }

    /**
     * @return int ms
     */
    protected function getHeartbeat(): int
    {
        $heartbeat = $this->config['heartbeat'];

        if ($heartbeat > 0) {
            return $heartbeat * 1000;
        }

        return 10 * 1000;
    }
}
