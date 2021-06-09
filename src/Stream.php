<?php

declare(strict_types = 1);

namespace SerendipitySwow\Nsq;

interface Stream
{

    public function read();

    public function write(string $data);

    public function close() : void;
}
