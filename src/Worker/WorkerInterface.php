<?php

namespace RxResque\Worker;

use React\Promise\Promise;
use RxResque\ContextInterface;
use RxResque\Task\TaskInterface;

interface WorkerInterface extends ContextInterface
{
    /**
     * Checks if the worker is currently idle.
     *
     * @return bool
     */
    public function isIdle(): bool;

    /**
     * Enqueues a task to be executed by the worker.
     *
     * @param TaskInterface $task The task to enqueue.
     *
     * @return Promise Resolves with the return value of Task::run().
     */
    public function enqueue(TaskInterface $task): Promise;

    /**
     * @return Promise Exit code.
     */
    public function shutdown(): Promise;
}