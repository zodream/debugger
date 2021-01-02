<?php
declare(strict_types=1);
namespace Zodream\Debugger\Domain;

use Exception;
use Zodream\Disk\Stream;
use Zodream\Helpers\Time;

class Timer {

    const STATUS_NONE = 0,
        STATUS_RUNNING = 1,
        STATUS_END = 2;

    /**
     * @var float
     */
    protected float $requestStartTime = 0;

    /**
     * @var float
     */
    protected float $requestEndTime = 0;

    protected array $startedMeasures = [];

    /**
     * @var array
     */
    protected array $measures = [];

    protected int $status = self::STATUS_NONE;

    public function __construct($requestStartTime = null)
    {
        if ($requestStartTime === null) {
            if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
                $requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'];
            } else {
                $requestStartTime = Time::millisecond();
            }
        }
        $this->requestStartTime = (float)$requestStartTime;
        $this->status = self::STATUS_RUNNING;
    }

    /**
     * Starts a measure
     *
     * @param string $name Internal name, used to stop the measure
     * @param string|null $label Public name
     * @param string|null $collector The source of the collector
     */
    public function startMeasure(string $name, $label = null, $collector = null)
    {
        $start = Time::millisecond();
        $this->startedMeasures[$name] = array(
            'label' => $label ?: $name,
            'start' => $start,
            'collector' => $collector
        );
    }

    /**
     * Check a measure exists
     *
     * @param string $name
     * @return bool
     */
    public function hasStartedMeasure(string $name): bool
    {
        return isset($this->startedMeasures[$name]);
    }

    /**
     * Stops a measure
     *
     * @param string $name
     * @param array $params
     * @throws Exception
     */
    public function stopMeasure(string $name, $params = array())
    {
        $end = Time::millisecond();
        if (!$this->hasStartedMeasure($name)) {
            throw new Exception("Failed stopping measure '$name' because it hasn't been started");
        }
        $this->addMeasure(
            $this->startedMeasures[$name]['label'],
            $this->startedMeasures[$name]['start'],
            $end,
            $params,
            $this->startedMeasures[$name]['collector']
        );
        unset($this->startedMeasures[$name]);
    }

    /**
     * Adds a measure
     *
     * @param string $label
     * @param float $start
     * @param float $end
     * @param array $params
     * @param string|null $collector
     */
    public function addMeasure(string $label, float $start, float $end, $params = array(), $collector = null)
    {
        $this->measures[] = array(
            'label' => $label,
            'start' => $start,
            'relative_start' => $start - $this->requestStartTime,
            'end' => $end,
            'relative_end' => $end - $this->requestEndTime,
            'duration' => $end - $start,
            'duration_str' => Time::formatDuration($end - $start),
            'params' => $params,
            'collector' => $collector
        );
    }

    /**
     * Utility function to measure the execution of a Closure
     *
     * @param string $label
     * @param \Closure $closure
     * @param string|null $collector
     * @return mixed
     * @throws Exception
     */
    public function measure(string $label, \Closure $closure, $collector = null)
    {
        $name = spl_object_hash($closure);
        $this->startMeasure($name, $label, $collector);
        $result = $closure();
        $params = is_array($result) ? $result : array();
        $this->stopMeasure($name, $params);
        return $result;
    }

    public function begin() {
        $this->requestEndTime = $this->requestStartTime = Time::millisecond();
        $this->status = self::STATUS_RUNNING;
	}

	public function record($name) {
	    $this->addMeasure($name, empty($this->measures)
            ? $this->requestStartTime :
            max(array_column($this->measures, 'end')), Time::millisecond());
    }
	
	public function end() {
        $this->requestEndTime = Time::millisecond();
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
	    return $this->requestEndTime - $this->requestStartTime;
    }

    /**
     * @return array
     */
	public function getTimes() {
	    return $this->measures;
    }

    public function toArray() {
	    $data = [
	        'start' => 0,
        ];
        usort($this->measures, function($a, $b) {
            if ($a['start'] == $b['start']) {
                return 0;
            }
            return $a['start'] < $b['start'] ? -1 : 1;
        });
	    foreach ($this->measures as $item) {
	        $data[$item['label']] = $item['duration'];
        }
	    $data['end'] = $this->requestEndTime - $this->requestStartTime;
	    return $data;
    }

    public function log() {
        $stream = new Stream(app_path('data/log/timer.log'));
        $stream->open('w')
            ->writeLine(Time::format())
            ->writeLine($this->requestStartTime);
        usort($this->measures, function($a, $b) {
            if ($a['start'] == $b['start']) {
                return 0;
            }
            return $a['start'] < $b['start'] ? -1 : 1;
        });
        foreach ($this->measures as $item) {
            $stream->writeLine(sprintf('%s:%s', $item['label'], $item['duration_str']));
        }
        $stream->writeLine($this->requestEndTime)
            ->close();
    }
}