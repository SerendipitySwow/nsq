<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SerendipitySwow\Nsq;

use JetBrains\PhpStorm\Pure;
use SerendipitySwow\Socket\Streams\Socket;

class Subscriber
{
    public const TYPE_RESPONSE = 0;

    public const TYPE_ERROR = 1;

    public const TYPE_MESSAGE = 2;

    protected Socket $socket;

    protected int $size;

    protected string $type = '';

    protected string $payload;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    public function recv(): Subscriber
    {
        $data = $this->socket->readChar(8);
        if ($data !== null) {
            $this->size = (int) sprintf('%u', unpack('N', substr($data, 0, 4))[1]);
            $this->type = sprintf('%u', unpack('N', substr($data, 4, 4))[1]);
            $length = $this->size - 4;
            $data = '';
            while ($len = $length - strlen($data)) {
                if ($len <= 0) {
                    break;
                }
                $data .= $this->socket->readChar($len);
            }
            $this->payload = Packer::unpackString($data);
        }

        return $this;
    }

    public function getMessage(): Message
    {
        return new Message($this->getPayload());
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getJsonPayload(): array
    {
        return json_decode($this->getPayload(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function isMessage(): bool
    {
        return (int) $this->type === self::TYPE_MESSAGE;
    }

    #[Pure]
    public function isHeartbeat(): bool
    {
        return $this->isMatchResponse('_heartbeat_');
    }

    #[Pure]
    public function isOk(): bool
    {
        return $this->isMatchResponse('OK');
    }

    #[Pure]
    private function isMatchResponse(
        $response
    ): bool {
        return (int) $this->type === self::TYPE_RESPONSE && $response === $this->getPayload();
    }
}
