<?php

namespace Zodream\Debugger\Domain\Debug;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class Dumper {
    /**
     * Dump a value with elegance.
     *
     * @param  mixed  $value
     * @return void
     */
    public static function dump($value) {
        if (!app('request')->isCli() && class_exists(CliDumper::class)) {
            $dumper = in_array(PHP_SAPI, ['cli', 'phpdbg']) ? new CliDumper : new HtmlDumper;

            $dumper->dump((new VarCloner)->cloneVar($value));
        } else {
            var_dump($value);
        }
    }

    public static function dumpResponse(...$args) {
        ob_start();
        foreach ($args as $x) {
            static::dump($x);
        }
        $res = ob_get_clean();
        response()->allowCors()->html($res)->send();
        die(1);
    }
}
