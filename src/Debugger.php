<?php
declare(strict_types=1);
namespace Zodream\Debugger;

use Throwable;
use Zodream\Database\Events\QueryExecuted;
use Zodream\Debugger\Domain\Bar;
use Zodream\Debugger\Domain\BlueScreen;
use Zodream\Service\Console\Output;
use Zodream\Template\Events\ViewCompiled;
use Zodream\Template\Events\ViewRendered;
use Zodream\Infrastructure\Contracts\Debugger as DebuggerInterface;

class Debugger implements DebuggerInterface {


    const COOKIE_SECRET = 'zd-debugger';

    protected bool $isDebug = false;

    protected bool $showBar = true;

    /**
     * @var Bar
     */
    protected $bar;

    protected bool $showFireLogger = true;

    protected bool $booted = false;

    protected int $maxLength = 150;

    protected int $maxDepth = 3;

    protected bool $showLocation = false;

    protected bool $reserved = false;

    protected $time;

    protected int $obLevel;

    protected ?array $cpuUsage;

    public function __construct() {
        $this->reserved = true;
        $this->time = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
        $this->obLevel = ob_get_level();
        $this->isDebug = app()->isDebug();
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
     * @param bool $showBar
     * @return Debugger
     */
    public function setShowBar(bool $showBar) {
        $this->showBar = $showBar;
        return $this;
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

    /**
     * @return Bar
     */
    public function getBar(): Bar {
        if (empty($this->bar)) {
            $this->bar = new Bar($this);
        }
        return $this->bar;
    }


    public function boot() {
        if ($this->booted) {
            return;
        }
//        if (!app()->hasBeenBootstrapped()) {
//
//        }
        register_shutdown_function([$this, 'shutdownHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        set_error_handler([$this, 'errorHandler']);
        $this->dispatch();
        $this->registerAssets();
        $this->booted = true;
        if (!$this->showBar || !$this->isDebug) {
            return;
        }
        event()->listen(QueryExecuted::class, function (QueryExecuted $executed) {
            $this->getBar()->appendQuery($executed->sql, $executed->bindings, $executed->time);
        });
        event()->listen(ViewCompiled::class, function (ViewCompiled $compiled) {
            $this->getBar()->appendView($compiled->file, $compiled->time, 'Compiled');
        });
        event()->listen(ViewRendered::class, function (ViewRendered $rendered) {
            $this->getBar()->appendView($rendered->file, $rendered->time);
        });
    }

    public function registerAssets() {
        if (request()->isAjax()
            || request()->isPjax()
            || request()->isPreFlight()
            || request()->isCli()) {
            return;
        }
        if (!app()->isDebug()) {
            return;
        }
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
        $this->reserved = false;
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR], true)) {
//            self::exceptionHandler(
//                Helpers::fixStack(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])),
//                false
//            );
            return;
        }
        if ($this->showBar && $this->isDebug) {
            $this->removeOutputBuffers(false);
            $this->getBar()->render();
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
        $this->reserved = false;
        if (request()->isCli()) {
            $this->renderForConsole(app(Output::class), $exception);
            return;
        }
        logger()->error($exception->getMessage());
        (new BlueScreen($this))->render($exception)->send();
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
     * @throws \Throwable
     * @internal
     */
    public function errorHandler($severity, $message, $file, $line, $context = []) {
        if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
            $e = new \ErrorException($message, 0, $severity, $file, $line);
            $e->context = $context;
            $this->exceptionHandler($e);
            return;
        }
        $this->getBar()->appendError($severity, $message, $file, $line);
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

    public function renderForConsole(Output $output, Throwable $e) {
        $output->writeln('');
        $output->writeln(sprintf('%s: %d', get_class($e), $e->getCode()));
        do {
            $output->writeln(sprintf('%s in %s: %d', $e->getMessage(), $e->getFile(), $e->getLine()));
        } while ($e = $e->getPrevious());
    }

}
