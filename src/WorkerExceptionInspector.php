<?php

namespace Twid\Octane;

use Throwable;
use Whoops\Exception\Inspector;

class WorkerExceptionInspector extends Inspector
{
    public function __construct(Throwable $throwable,  string $class,  array $trace)
    {
        parent::__construct($throwable);
    }

    /**
     * Get the worker exception name.
     *
     * @return string
     */
    public function getExceptionName()
    {
        return $this->class;
    }

    /**
     * Get the worker exception trace.
     *
     * @param  \Throwable  $throwable
     * @return array
     */
    public function getTrace($throwable)
    {
        return $this->trace;
    }
}
