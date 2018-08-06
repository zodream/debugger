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
}