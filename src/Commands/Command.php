<?php

namespace Twid\Octane\Commands;

use Illuminate\Console\Command as BaseCommand;
use Twid\Octane\Commands\Concerns\InteractsWithIO;
use Twid\Octane\Stringable;

class Command extends BaseCommand
{
    use InteractsWithIO;
    /**
     * Get a new stringable object from the given string.
     *
     * @param  string  $string
     * @return Stringable
     */
    public static function of($string)
    {
        return new Stringable($string);
    }
}
