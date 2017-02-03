<?php

namespace RxResque\Task;

class SuccessfulTask implements TaskResultInterface
{
    /** @var mixed */
    private $result;

    public function __construct($result) {
        $this->result = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult() {
        return $this->result;
    }
}