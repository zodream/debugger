<?php
namespace Zodream\Debugger\Domain;


use Zodream\Debugger\Debugger;

abstract class BaseBox {

    /**
     * @var Debugger
     */
    protected $debugger;

    public function __construct(Debugger $debugger) {
        $this->setDebugger($debugger);
    }

    /**
     * @param Debugger $debugger
     */
    public function setDebugger(Debugger $debugger) {
        $this->debugger = $debugger;
    }

    protected function errorTypeToString($type) {
        $types = [
            E_ERROR => 'Fatal Error',
            E_USER_ERROR => 'User Error',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_CORE_ERROR => 'Core Error',
            E_COMPILE_ERROR => 'Compile Error',
            E_PARSE => 'Parse Error',
            E_WARNING => 'Warning',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_WARNING => 'User Warning',
            E_NOTICE => 'Notice',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict standards',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
        ];
        return isset($types[$type]) ? $types[$type] : 'Unknown error';
    }
}