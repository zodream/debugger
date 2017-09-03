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
     * Zodream\Module\Debugger\Domain\Debugger::dump() shortcut.
     * @tracySkipLocation
     * @param $var
     * @return mixed
     */
	function dump($var) {
		array_map('Zodream\Module\Debugger\Domain\Debugger::dump', func_get_args());
		return $var;
	}
}

if (!function_exists('bdump')) {
    /**
     * Zodream\Module\Debugger\Domain\Debugger::barDump() shortcut.
     * @tracySkipLocation
     * @param $var
     * @return mixed
     */
	function bdump($var) {
		call_user_func_array('Zodream\Module\Debugger\Domain\Debugger::barDump', func_get_args());
		return $var;
	}
}
