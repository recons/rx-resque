<?php

namespace RxResque;

use RxResque\Task\TaskInterface;

class SampleTask implements TaskInterface
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Runs the task inside the caller's context.
     */
    public function run()
    {
        sleep(3);
        $i = 1;
        return '123';
    }
}