<?php

namespace RxResque\Task;

interface TaskInterface
{
    /**
     * Runs the task inside the caller's context.
     */
    public function run();
}