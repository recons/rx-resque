<?php
declare(strict_types = 1);

namespace RxResque\Channel;

use React\Promise\Deferred;
use React\Promise\Promise;
use React\Stream\Stream;
use RxResque\Exception\ContextException;

class Channel implements ChannelInterface, StreamChannelInterface
{
    /** @var Stream */
    private $read;

    /** @var Stream */
    private $write;

    public function __construct(Stream $read, Stream $write)
    {
        $this->read = $read;
        $this->write = $write;
    }

    /**
     * @inheritdoc
     */
    public function send($data)
    {
        $serialized = serialize($data);
        $this->write->write($serialized);
    }

    /**
     * @inheritdoc
     */
    public function receive(): Promise
    {
        $deferred = new Deferred();

        $onData = function ($raw) use ($deferred) {
            $data = unserialize($raw);
            $deferred->resolve($data);
        };

        $onClose = function () use ($deferred) {
            $deferred->reject(new ContextException('Context has died'));
        };

        $this->read->on('data', $onData);
        $this->write->on('close', $onClose);

        return $deferred->promise()
            ->always(function () use ($onData, $onClose) {
                $this->read->removeListener('data', $onData);
                $this->write->removeListener('close', $onClose);
            });
    }
}