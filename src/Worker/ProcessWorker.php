<?php
declare(strict_types=1);

namespace RxResque\Worker;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use RxResque\Process\ChanneledProcess;
use RxResque\StrandInterface;
use RxResque\Task\TaskInterface;

class ProcessWorker implements WorkerInterface
{
    /** @var StrandInterface */
    private $strand;

    /** @var bool */
    private $isIdle = true;

    /** @var bool */
    private $shutdown = false;

    public function __construct(LoopInterface $loop)
    {
        $dir = \dirname(__DIR__, 2) . '/bin';
        $this->strand = new ChanneledProcess($loop, "$dir/worker.php", $dir);
    }

    /**
     * @inheritdoc
     */
    public function isRunning(): bool
    {
        return $this->strand->isRunning();
    }

    /**
     * @inheritdoc
     */
    public function isIdle(): bool
    {
        return $this->isIdle;
    }

    /**
     * @inheritdoc
     */
    public function start()
    {
        $this->strand->start();
    }

    /**
     * @inheritdoc
     */
    public function enqueue(TaskInterface $task): Promise
    {
        $deferred = new Deferred();

        switch (true) {
            case !$this->isIdle():
                $deferred->reject(new \Exception('The worker is busy'));
                break;
            case !$this->strand->isRunning():
                $deferred->reject(new \Exception('The worker has not been started.'));
                break;
            case $this->shutdown:
                $deferred->reject(new \Exception('The worker has been shut down.'));
                break;
            default:
                $this->isIdle = false;
                $this->strand->send($task);
                $deferred->resolve(
                    $this->strand->receive()
                        ->always(function () {
                            $this->isIdle = true;
                        })
                );
                break;
        }

        return $deferred->promise();
    }

    /**
     * @inheritdoc
     */
    public function shutdown(): Promise
    {
        // TODO
    }

    /**
     * @inheritdoc
     */
    public function kill()
    {
        $this->strand->kill();
    }
}