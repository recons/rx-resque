<?php

namespace RxResque\Task;

interface TaskResultInterface
{
    /**
     * Get the result of task execution
     *
     * @return mixed
     *
     */
    public function getResult();
}