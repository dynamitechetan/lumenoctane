<?php

namespace Twid\Octane;

use Twid\Octane\Stringable;

class Str extends \Illuminate\Support\Str
{


    /**
     * The callback that should be used to generate UUIDs.
     *
     * @var callable
     */
    protected static $uuidFactory;

    /**
     * Get a new stringable object from the given string.
     *
     * @param  string  $string
     * @return \Twid\Octane\Stringable
     */
    public static function of($string)
    {
        return new Stringable($string);
    }

}
