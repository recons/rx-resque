<?php
declare(strict_types = 1);

namespace RxResque\Channel;

use React\Promise\Deferred;
use React\Promise\Promise;
use React\Stream\Stream;

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
        try {
            $serialized = serialize($data);
            $this->write->write($serialized);
        } catch (\Throwable $exception) {
            file_put_contents( __DIR__ . '/log.log', $exception->getMessage(), FILE_APPEND);
            die;
        }
    }

    /**
     * @inheritdoc
     */
    public function receive(): Promise
    {
        $deferred = new Deferred();

        $this->read->once('data', function ($raw) use ($deferred) {
            $data = unserialize($raw);
            $deferred->resolve($data);
        });

        $this->write->once('close', function () use ($deferred) {
            $deferred->reject(new \Exception('lolololable'));
        });

        return $deferred->promise();
    }
}