<?php
namespace Zodream\Debugger;

use Exception;
use Zodream\Debugger\Domain\Dumper;

class Debugger {


    const COOKIE_SECRET = 'zd-debugger';

    protected $isDebug = false;

    protected $showBar = true;

    protected $showFireLogger = true;

    protected $booted = false;

    protected $maxLength = 150;

    protected $maxDepth = 3;

    protected $showLocation = false;

    protected $reserved;

    protected $time;

    protected $obLevel;

    protected $cpuUsage;

    public function __construct() {
        $this->isDebug = app()->isDebug();
        $this->reserved = str_repeat('t', 30000);
        $this->time = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
        $this->obLevel = ob_get_level();
        $this->cpuUsage = $this->isDebug && function_exists('getrusage') ? getrusage() : null;
        // php configuration
        ini_set('display_errors', $this->isDebug ? '1' : '0'); // or 'stderr'
        ini_set('html_errors', '0');
        ini_set('log_errors', '0');

        error_reporting(E_ALL);

        $this->boot();
    }



    public function boot() {
        if ($this->booted) {
            return;
        }
//        register_shutdown_function([$this, 'shutdownHandler']);
//        set_exception_handler([$this, 'exceptionHandler']);
//        set_error_handler([$this, 'errorHandler']);
//
//        $this->dispatch();
        $this->booted = true;
    }

    public function dispatch() {
        if (!$this->isDebug || PHP_SAPI === 'cli') {
            return;

        }
        if (headers_sent($file, $line) || ob_get_length()) {
            throw new \LogicException(
                __METHOD__ . '() called after some output has been sent. '
                . ($file ? "Output started at $file:$line." : 'Try Tracy\OutputDebugger to find where output started.')
            );

        }

    }

    public function shutdownHandler() {

    }


    /**
     * Handler to catch uncaught exception.
     * @param  \Exception|\Throwable $exception
     * @param bool $exit
     * @return void
     * @internal
     */
    public function exceptionHandler($exception, $exit = true) {

    }


    /**
     * Handler to catch warnings and notices.
     * @param $severity
     * @param $message
     * @param $file
     * @param $line
     * @param array $context
     * @return void false to call normal error handler, null otherwise
     * @internal
     */
    public static function errorHandler($severity, $message, $file, $line, $context = []) {

    }


    public static function dump($var, $return = false) {
        if ($return) {
            ob_start(function () {});
            Dumper::dump($var, [
                Dumper::DEPTH => self::$maxDepth,
                Dumper::TRUNCATE => self::$maxLength,
            ]);
            return ob_get_clean();

        } elseif (!self::$productionMode) {
            Dumper::dump($var, [
                Dumper::DEPTH => self::$maxDepth,
                Dumper::TRUNCATE => self::$maxLength,
                Dumper::LOCATION => self::$showLocation,
            ]);
        }

        return $var;
    }


}
