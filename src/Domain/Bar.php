<?php
declare(strict_types=1);
namespace Zodream\Debugger\Domain;

use Zodream\Disk\FileSystem;
use Zodream\Infrastructure\Support\Html;

class Bar extends BaseBox {

    protected array $data = [
        'Queries' => [],
        'Views' => [],
        'Errors' => [],
    ];

    public function appendQuery(string $sql, array $bindings, float $time): static {
        $this->data['Queries'][] = sprintf('[%sms] %s', $time, $sql);
        return $this;
    }

    public function appendView(mixed $file, float $time, string $type = 'Rendered'): static {
        $this->data['Views'][] = sprintf('[%s] %s : %sms', $type,
            FileSystem::relativePath((string)app_path(), (string)$file), $time);
        return $this;
    }

    public function appendError($severity, $message, $file, $line): static {
        $message = 'PHP ' . $this->errorTypeToString($severity) . ': '.$message;
        $this->data['Errors'][] = sprintf('%s[%s]: %s', $file, $line, $message);
        return $this;
    }

    public function render(): void {
        if (app('request')->isAjax()
            || app('request')->isPjax()
            || app('request')->isPreFlight()
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
        $times = timer()->endIfNot()->toArray();
        $data = [
            __('Error Message') => $this->data['Errors'],
            __('System Info') => $info,
            __('Execute Info') => array_map([$this, 'formatTime'], $times),
            __('Queries({count})', ['count' => count($this->data['Queries'])]) => $this->data['Queries'],
            __('Views({count})', ['count' => count($this->data['Views'])]) => $this->data['Views']
        ];
        $data = json_encode(array_filter($data));
        $error_count = count($this->data['Errors']);
        $html = '';
        foreach ([
            '@debugger.css'
                 ] as $file) {
            $html .= Html::link(view()->getAssetUri($file));
        }
        foreach ([
            '@debugger.min.js'
                 ] as $file) {
            $html .= Html::script('', [
                'src' =>view()->getAssetUri($file)
            ]);
        }
        echo <<<HTML
{$html}
<script>
Debugger.bar('{$time}', {$error_count}, {$data});
</script>
HTML;

    }

    protected function getCpuUsage(float $time): array {
        $data = $this->debugger->getUsedCpuUsage();
        return [
            -round(($data['ru_utime.tv_sec'] * 1e6 + $data['ru_utime.tv_usec']) / $time / 10000),
            -round(($data['ru_stime.tv_sec'] * 1e6 + $data['ru_stime.tv_usec']) / $time / 10000)
        ];
    }

    protected function getUseClassCount(array $list): int {
        return count(array_filter($list, function ($name) {
            return (new \ReflectionClass($name))->isUserDefined();
        }));
    }

    protected function formatTime(float $time): string {
        return number_format( $time * 1000, 1, '.', ' ') . ' ms';
    }

    protected function getProperties(): array {
        $time = $this->debugger->getUsedTime();
        list($userUsage, $systemUsage) = $this->getCpuUsage($time);
        $opcache = function_exists('opcache_get_status') ? @opcache_get_status() : null; // @ can be restricted
        $cachedFiles = isset($opcache['scripts']) ? array_intersect(array_keys($opcache['scripts']), get_included_files()) : [];
        $info = [
            'Execution time' => $this->formatTime($time),
            'CPU usage user + system' => isset($userUsage) ? (int) $userUsage . ' % + ' . (int) $systemUsage . ' %' : null,
            'Peak of allocated memory' => number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') . ' MB',
            'Included files' => count(get_included_files()),
            'OPcache' => $opcache ? round(count($cachedFiles) * 100 / count(get_included_files())) . '% cached' : null,
            'Classes + interfaces + traits' => $this->getUseClassCount(get_declared_classes()) . ' + '
                . $this->getUseClassCount(get_declared_interfaces()) . ' + ' . $this->getUseClassCount(get_declared_traits()),
            'Your IP' => $_SERVER['REMOTE_ADDR'] ?? null,
            'Server IP' => $_SERVER['SERVER_ADDR'] ?? null,
            'HTTP method / response code' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] . ' / ' . http_response_code() : null,
            'PHP' => PHP_VERSION,
            'Xdebug' => extension_loaded('xdebug') ? phpversion('xdebug') : null,
            'Zodream' => app()->version(),
            'Server' => $_SERVER['SERVER_SOFTWARE'] ?? null,
        ];
        return array_filter($info);
    }
}