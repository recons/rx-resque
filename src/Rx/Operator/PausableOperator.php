<?php
declare(strict_types = 1);

namespace RxResque\Rx\Operator;

use Rx\ObservableInterface;
use Rx\ObserverInterface;
use Rx\Operator\OperatorInterface;
use Rx\SchedulerInterface;
use Rx\Subject\Subject;
use RxResque\Rx\Observable\PausableObservable;

class PausableOperator implements OperatorInterface
{
    /** @var Subject */
    private $pauser;

    public function __construct(Subject $pauser)
    {
        $this->pauser = $pauser;
    }

    /**
     * @param \Rx\ObservableInterface $observable
     * @param \Rx\ObserverInterface $observer
     * @param \Rx\SchedulerInterface $scheduler
     * @return \Rx\DisposableInterface
     */
    public function __invoke(ObservableInterface $observable, ObserverInterface $observer, SchedulerInterface $scheduler = null)
    {
        $pausable = new PausableObservable($observable, $this->pauser);

        return $pausable->subscribe($observer, $scheduler);

    }
}