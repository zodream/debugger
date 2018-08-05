<?php


if (!function_exists('dd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function dd() {
        call_user_func_array('dump', func_get_args());
        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Zodream\Debugger\Domain\Debugger::dump() shortcut.
     * @tracySkipLocation
     * @param $var
     * @return mixed
     */
	function dump($var) {
		array_map(function ($item) {
		    return app('debugger')->dump($item);
        }, func_get_args());
		return $var;
	}
}

