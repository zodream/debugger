<?php
namespace Zodream\Debugger\Domain;


class BlueScreen extends BaseBox {

    public function render($exception) {
        $info = json_encode($this->getInfo($exception));
        $html = view()->header().view()->footer();
        $exceptions = json_encode($this->getAllException($exception));
        return <<<HTML
<html>
<body>
{$html}
<script>
Debugger.blueScreen({$info}, {$exceptions});
</script>
</body>
</html>
HTML;
    }

    protected function getInfo($exception) {
        return $this->formatException($exception);
    }

    protected function formatException($exception) {
        return [
            'name' => $exception instanceof \ErrorException
                ? $this->errorTypeToString($exception->getSeverity())
                : $this->getClass($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
    }

    protected function formatTrace(array $stace) {
        return $stace;
    }

    protected function formatSource($file, $line) {
        return '';
    }

    protected function getAllException($exception) {
        $data = [];
        do {
            $info = $this->formatException($exception);
            $stack = $exception->getTrace();
            $info['trace'] = $this->formatTrace($stack);
            $info['source'] = $this->formatSource($info['file'], $info['line']);
            $data[] = $info;
        } while ($exception = $exception->getPrevious());
        return $data;
    }



    protected function get($exception) {
        $lastError = $exception instanceof \ErrorException || $exception instanceof \Error ? null : error_get_last();
    }

    protected function getClass($obj) {
        return explode("\x00", get_class($obj))[0];
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