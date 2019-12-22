<?php
namespace Zodream\Debugger\Domain;

use Zodream\Disk\Stream;
use Zodream\Service\Factory;
use Zodream\Helpers\Time;

class Timer {

    const STATUS_NONE = 0,
        STATUS_RUNNING = 1,
        STATUS_END = 2;

	protected $startTime;

    protected $lastTime;

    protected $times = [];

    protected $status = self::STATUS_NONE;

    public function __construct() {
        $this->begin();
    }

    public function begin() {
        $this->lastTime = $this->startTime = Time::millisecond();
        $this->times = [
            'begin' => 0
        ];
        $this->status = self::STATUS_RUNNING;
	}

	public function record($name) {
	    $arg = Time::millisecond();
        if (array_key_exists($name, $this->times)) {
            $name .= time();
        }
        $this->times[$name] = $arg - $this->lastTime;
        $this->lastTime = $arg;
    }
	
	public function end() {
	    $this->record('end');
	    $this->status = self::STATUS_END;
		return $this->getCount();
	}

	public function endIfNot() {
	    if ($this->status == self::STATUS_RUNNING) {
	        $this->end();
        }
        return $this;
    }

	public function getCount() {
	    return $this->lastTime - $this->startTime;
    }

    /**
     * @return array
     */
	public function getTimes() {
	    return $this->times;
    }

    public function log() {
        $stream = new Stream(Factory::root()->file('data/log/timer.log'));
        $stream->open('w')
            ->writeLine(Time::format())
            ->writeLine($this->startTime);
        foreach ($this->times as $key => $item) {
            $stream->writeLine($key.':'.$item);
        }
        $stream->writeLine($this->lastTime)
            ->close();
    }
}