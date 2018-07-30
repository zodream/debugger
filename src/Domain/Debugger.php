<?php
namespace Zodream\Debugger\Domain;

use Exception;

class Debugger {

    const
        DEVELOPMENT = false,
        PRODUCTION = true;

    const COOKIE_SECRET = 'zd-debugger';

    protected $mode = self::DEVELOPMENT;

    protected $showBar = true;

}
