<?php

namespace RxResque\Channel;

use React\Stream\Stream;

interface StreamChannelInterface
{
    public function __construct(Stream $read, Stream $write);
}