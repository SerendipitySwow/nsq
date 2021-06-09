<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq\Exception;

use JetBrains\PhpStorm\Pure;

final class AuthenticationRequired extends NsqException
{
    #[Pure]
    public function __construct()
    {
        parent::__construct('NSQ requires authorization, set ClientConfig::$authSecret before connecting');
    }
}
