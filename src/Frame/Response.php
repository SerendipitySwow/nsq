<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq\Frame;

use JetBrains\PhpStorm\Pure;
use SerendipitySwow\Nsq\Frame;

/**
 * @psalm-immutable
 */
final class Response extends Frame
{
    public const OK = 'OK';
    public const HEARTBEAT = '_heartbeat_';

    #[Pure]
    public function __construct(
        public string $data
    ) {
        parent::__construct(self::TYPE_RESPONSE);
    }

    public function isOk() : bool
    {
        return self::OK === $this->data;
    }

    public function isHeartBeat() : bool
    {
        return self::HEARTBEAT === $this->data;
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public function toArray() : array
    {
        return json_decode($this->data, true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);
    }
}
