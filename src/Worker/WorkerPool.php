<?php
declare(strict_types=1);

namespace RxResque\Worker;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\Timer\TimeoutException;
use RxResque\Exception\ContextException;
use RxResque\Task\TaskInterface;
use RxResque\Task\TaskResultInterface;

class WorkerPool extends EventEmitter implements PoolInterface
{
    /** @var LoopInterface */
    private $loop;

    /** @var int */
    private $minSize;

    /** @var int */
    private $maxSize;

    /** @var \SplObjectStorage */
    private $workers;

    /** @var \SplQueue */
    private $idleWorkers;

    /** @var \SplQueue */
    private $busyWorkers;

    /** @var bool */
    private $running = false;

    public function __construct(LoopInterface $loop, int $minSize = -1, int $maxSize= -1)
    {
        $this->loop = $loop;

        $this->minSize = $minSize === -1 ? self::DEFAULT_MIN_SIZE : $minSize;
        $this->maxSize = $maxSize === -1 ? self::DEFAULT_MAX_SIZE : $maxSize;

        if ($this->minSize < 0) {
            throw new ContextException('Minimum size must be a non-negative integer.');
        }

        if ($this->maxSize < 0 || $this->maxSize < $this->minSize) {
            throw new ContextException('Maximum size must be a non-negative integer at least ' . $minSize . '.');
        }

        $this->workers = new \SplObjectStorage();
        $this->idleWorkers = new \SplQueue();
        $this->busyWorkers = new \SplQueue();
    }

    /**
     * @inheritdoc
     */
    public function getWorkerCount(): int
    {
        return $this->workers->count();
    }

    /**
     * @inheritdoc
     */
    public function getIdleWorkerCount(): int
    {
        return $this->idleWorkers->count();
    }

    public function getBusyWorkerCount(): int
    {
        return $this->busyWorkers->count();
    }

    /**
     * @inheritdoc
     */
    public function getMinSize(): int
    {
        return $this->minSize;
    }

    /**
     * @inheritdoc
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * @inheritdoc
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * @inheritdoc
     */
    public function isIdle(): bool
    {
        return $this->idleWorkers->count() > 0 || $this->workers->count() < $this->maxSize;
    }

    /**
     * @inheritdoc
     */
    public function start()
    {
        if ($this->isRunning()) {
            throw new ContextException('The worker pool has already been started.');
        }

        $count = $this->minSize;
        while (--$count >= 0) {
            $worker = $this->createWorker();
            $this->idleWorkers->enqueue($worker);
        }

        $this->running = true;

        $this->emit('status', [$this->isIdle()]);
    }

    /**
     * Creates a worker and adds them to the pool.
     *
     * @return WorkerInterface The worker created.
     */
    private function createWorker(): WorkerInterface
    {
        $worker = new ProcessWorker($this->loop);
        $worker->start();

        $this->workers->attach($worker);

        return $worker;
    }

    /**
     * @inheritdoc
     */
    public function enqueue(TaskInterface $task): Promise
    {
        $worker = $this->pull();

        $promise = $worker->enqueue($task)
            ->always(function () use ($worker) {
                $this->push($worker);
            });

        return \React\Promise\Timer\timeout($promise, 5, $this->loop)
            ->then(function (TaskResultInterface $taskResult) {
                return $taskResult->getResult();
            })
            ->otherwise(function (TimeoutException $exception) use ($worker) {
                $worker->kill();
                throw $exception;
            });
    }

    /**
     * @inheritdoc
     */
    public function shutdown(): Promise
    {
        // TODO: Implement shutdown() method.
    }

    /**
     * @inheritdoc
     */
    public function kill()
    {
        // TODO: Implement kill() method.
    }

    protected function pull(): WorkerInterface
    {
        if (!$this->isRunning()) {
            throw new ContextException("The pool is not running");
        }

        if (!$this->isIdle()) {
            throw new ContextException('All possible workers busy');
        }

        $worker = !$this->idleWorkers->isEmpty() ?
            $this->idleWorkers->shift() :
            $this->createWorker();

        $this->busyWorkers->push($worker);
        $this->emit('status', [$this->isIdle()]);

        return $worker;
    }

    protected function push(WorkerInterface $worker)
    {
        if (!$this->workers->contains($worker)) {
            throw new ContextException("The provided worker was not part of this queue");
        }

        $key = array_search($worker, (array)$this->busyWorkers);
        unset($this->busyWorkers[$key]);

        if ($worker->isRunning()) {
            $this->idleWorkers->push($worker);
        } else {
            $this->workers->detach($worker);
        }

        $this->emit('status', [$this->isIdle()]);
    }
}