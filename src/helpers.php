<?php

use Zodream\Debugger\Domain\Debug\Dumper;


if (! function_exists('dd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed  $args
     * @return void
     */
    function dd(...$args): void {
        (new Dumper())->dumpResponse(...$args);
    }
}

if (! function_exists('dr')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed  $args
     * @return void
     */
    function dr(...$args): void {
        (new Dumper())->dumpResponse(...$args);
    }
}


