<?php
namespace Zodream\Debugger\Domain;


use Zodream\Debugger\Debugger;

class Bar {

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

    public function render() {
        $time = number_format($this->debugger->getUsedTime() * 1000, 1, '.', ' ');
        return <<<JS
DebuggerBar.init({$time});
JS;

    }
}