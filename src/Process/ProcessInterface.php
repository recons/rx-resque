<?php

namespace RxResque\Process;

interface ProcessInterface
{
    /**
     * @return int PID of process.
     */
    public function getPid(): int;
}