<?php
namespace Zodream\Debugger\Domain;

use Zodream\Disk\FileSystem;
use Zodream\Service\Factory;

class Bar extends BaseBox {

    protected $data = [
        'Queries' => [],
        'Views' => [],
        'Errors' => [],
    ];

    public function appendQuery($sql, $bindings, $time) {
        $this->data['Queries'][] = sprintf('[%sms] %s', $time, $sql);
        return $this;
    }

    public function appendView($file, $time, $type = 'Rendered') {
        $this->data['Views'][] = sprintf('[%s] %s : %sms', $type, FileSystem::relativePath(Factory::root(), $file), $time);
        return $this;
    }

    public function appendError($severity, $message, $file, $line) {
        $message = 'PHP ' . $this->errorTypeToString($severity) . ': '.$message;
        $this->data['Errors'][] = sprintf('%s[%s]: %s', $file, $line, $message);
        return $this;
    }

    public function render() {
        if (app('request')->isAjax()
            || app('request')->isPjax()
            || app('request')->isCli()) {
            return;
        }
        $header_list = implode("\n", headers_list());
        if (preg_match('#^Location:#im', $header_list)
            || preg_match('#^Content-Type: (?!text/html)#im', $header_list)) {
            return;
        }
        $info = $this->getProperties();
        $time = $info['Execution time'];
        $times = app('timer')->endIfNot()->getTimes();
        $data = [
            __('Error Message') => $this->data['Errors'],
            __('System Info') => $info,
            __('Execute Info') => array_map([$this, 'formatTime'], $times),
            __('Queries({count})', ['count' => count($this->data['Queries'])]) => $this->data['Queries'],
            __('Views({count})', ['count' => count($this->data['Views'])]) => $this->data['Views']
        ];
        $data = json_encode(array_filter($data));
        $error_count = count($this->data['Errors']);
        echo <<<HTML
<script>
Debugger.bar('{$time}', {$error_count}, {$data});
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

    protected function formatTime($time) {
        return number_format( $time * 1000, 1, '.', ' ') . ' ms';
    }

    protected function getProperties() {
        $time = $this->debugger->getUsedTime();
        list($userUsage, $systemUsage) = $this->getCpuUsage($time);
        $opcache = function_exists('opcache_get_status') ? @opcache_get_status() : null; // @ can be restricted
        $cachedFiles = isset($opcache['scripts']) ? array_intersect(array_keys($opcache['scripts']), get_included_files()) : [];
        $info = [
            'Execution time' => $this->formatTime($time),
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