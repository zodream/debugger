<?php
namespace Zodream\Debugger\Domain;

class Bar extends BaseBox {

    protected $errors = [];

    public function appendError($severity, $message, $file, $line) {
        $message = 'PHP ' . $this->errorTypeToString($severity) . ": $message";
        $this->errors[] = "$file[$line]: $message";
    }

    public function render() {
        $info = $this->getProperties();
        $time = $info['Execution time'];
        $info = json_encode($info);
        $errors = json_encode($this->errors);
        return <<<HTML
<script>
Debugger.bar('{$time}', {$info}, {$errors});
</script>
HTML;

    }

    protected function getCpuUsage($time) {
        $data = $this->debugger->getUsedCpuUsage();
        return [
            -round(($data['ru_utime.tv_sec'] * 1e6 + $data['ru_utime.tv_usec']) / $time / 10000),
            -round(($data['ru_stime.tv_sec'] * 1e6 + $data['ru_stime.tv_usec']) / $time / 10000)
        ];
    }

    protected function getUseClassCount($list) {
        return count(array_filter($list, function ($name) {
            return (new \ReflectionClass($name))->isUserDefined();
        }));
    }

    protected function getProperties() {
        $time = $this->debugger->getUsedTime();
        list($userUsage, $systemUsage) = $this->getCpuUsage($time);
        $opcache = function_exists('opcache_get_status') ? @opcache_get_status() : null; // @ can be restricted
        $cachedFiles = isset($opcache['scripts']) ? array_intersect(array_keys($opcache['scripts']), get_included_files()) : [];
        $info = [
            'Execution time' => number_format( $time * 1000, 1, '.', ' ') . ' ms',
            'CPU usage user + system' => isset($userUsage) ? (int) $userUsage . ' % + ' . (int) $systemUsage . ' %' : null,
            'Peak of allocated memory' => number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') . ' MB',
            'Included files' => count(get_included_files()),
            'OPcache' => $opcache ? round(count($cachedFiles) * 100 / count(get_included_files())) . '% cached' : null,
            'Classes + interfaces + traits' => $this->getUseClassCount(get_declared_classes()) . ' + '
                . $this->getUseClassCount(get_declared_interfaces()) . ' + ' . $this->getUseClassCount(get_declared_traits()),
            'Your IP' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            'Server IP' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null,
            'HTTP method / response code' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] . ' / ' . http_response_code() : null,
            'PHP' => PHP_VERSION,
            'Xdebug' => extension_loaded('xdebug') ? phpversion('xdebug') : null,
            'Zodream' => app()->version(),
            'Server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null,
        ];
        return array_filter($info);
    }
}