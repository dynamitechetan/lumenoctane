<?php

namespace Twid\Octane\Exceptions;

use Laravel\SerializableClosure\Support\ClosureStream;

class TaskExceptionResult
{
    public function __construct(
         string $class,
         string $message,
         int $code,
         string $file,
         int $line
    ) {
        $this->class = $class;
        $this->message = $message;
        $this->code = $code;
        $this->file = $file;
        $this->line = $line;
    }

    /**
     * Creates a new task exception result from the given throwable.
     *
     * @param  \Throwable  $throwable
     * @return \Twid\Octane\Exceptions\TaskExceptionResult
     */
    public static function from($throwable)
    {
        $fallbackTrace = str_starts_with($throwable->getFile(), ClosureStream::STREAM_PROTO.'://')
            ? collect($throwable->getTrace())->whereNotNull('file')->first()
            : null;

        return new static(
            get_class($throwable),
            $throwable->getMessage(),
            (int) $throwable->getCode(),
            $fallbackTrace['file'] ?? $throwable->getFile(),
            $fallbackTrace['line'] ?? (int) $throwable->getLine(),
        );
    }

    /**
     * Gets the original throwable.
     *
     * @return \Twid\Octane\Exceptions\TaskException|\Twid\Octane\Exceptions\DdException
     */
    public function getOriginal()
    {
        if ($this->class == DdException::class) {
            return new DdException(
                json_decode($this->message, true)
            );
        }

        return new TaskException(
            $this->class,
            $this->message,
            (int) $this->code,
            $this->file,
            (int) $this->line,
        );
    }
}
