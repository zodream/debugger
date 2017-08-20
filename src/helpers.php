<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

if (!function_exists('dump')) {
    /**
     * Zodream\Module\Debugger\Domain\Debugger::dump() shortcut.
     * @tracySkipLocation
     * @param $var
     * @return mixed
     */
	function dump($var)
	{
		array_map('Zodream\Module\Debugger\Domain\Debugger::dump', func_get_args());
		return $var;
	}
}

if (!function_exists('bdump')) {
	/**
	 * Zodream\Module\Debugger\Domain\Debugger::barDump() shortcut.
	 * @tracySkipLocation
	 */
	function bdump($var)
	{
		call_user_func_array('Zodream\Module\Debugger\Domain\Debugger::barDump', func_get_args());
		return $var;
	}
}
