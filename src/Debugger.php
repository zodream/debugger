<?php
namespace Zodream\Debugger;

use Exception;
use Zodream\Debugger\Domain\Bar;
use Zodream\Debugger\Domain\BlueScreen;
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

    /**
     * @return integer
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * @return integer
     */
    public function getUsedTime() {
        return microtime(true) - $this->time;
    }

    /**
     * @return array|null
     */
    public function getCpuUsage(): array {
        return $this->cpuUsage;
    }

    public function getUsedCpuUsage(): array {
        $data = [];
        foreach (getrusage() as $key => $val) {
            $data[$key] = $this->cpuUsage[$key] - $val;
        }
        return $data;
    }



    public function boot() {
        if ($this->booted) {
            return;
        }
        register_shutdown_function([$this, 'shutdownHandler']);
//        set_exception_handler([$this, 'exceptionHandler']);
//        set_error_handler([$this, 'errorHandler']);

        $this->dispatch();
        $this->registerAssets();
        $this->booted = true;
    }

    public function registerAssets() {
        view()->registerJsFile('@jquery.min.js')
            ->registerJsFile('@debugger.min.js')
            ->registerCssFile('@font-awesome.min.css')
            ->registerCssFile('@zodream.css')
            ->registerCssFile('@debugger.css');
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
        if (!$this->reserved) {
            return;
        }
        $this->reserved = null;
        if (app('request')->isAjax()) {
            return;
        }

        $error = error_get_last();
        if (in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR], true)) {
//            self::exceptionHandler(
//                Helpers::fixStack(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])),
//                false
//            );
            return;
        }
        if ($this->showBar && $this->isDebug) {
            $this->removeOutputBuffers(false);
            echo (new Bar($this))->render();
        }
    }


    /**
     * Handler to catch uncaught exception.
     * @param  \Exception|\Throwable $exception
     * @param bool $exit
     * @return void
     * @internal
     */
    public function exceptionHandler($exception, $exit = true) {
        if (!$this->reserved && $exit) {
            return;
        }
        $this->reserved = null;
        echo (new BlueScreen($this))->render($exception);
        if ($exit) {
            exit(255);
        }
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
    public function errorHandler($severity, $message, $file, $line, $context = []) {
        $e = new \ErrorException($message, 0, $severity, $file, $line);
        $e->context = $context;
        $this->exceptionHandler($e);
    }

    protected function removeOutputBuffers($errorOccurred) {
        while (ob_get_level() > $this->obLevel) {
            $status = ob_get_status();
            if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
                break;
            }
            $fnc = $status['chunk_size'] || !$errorOccurred ? 'ob_end_flush' : 'ob_end_clean';
            if (!@$fnc()) { // @ may be not removable
                break;
            }
        }
    }



}
