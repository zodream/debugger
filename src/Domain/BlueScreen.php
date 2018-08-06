<?php
namespace Zodream\Debugger\Domain;


class BlueScreen extends BaseBox {

    public function render($exception) {
        $info = json_encode($this->getInfo($exception));
        $html = view()->header().view()->footer();
        return <<<HTML
<html>
<body>
{$html}
<script>
Debugger.blueScreen({$info});
</script>
</body>
</html>
HTML;
    }

    protected function getInfo($exception) {
        return [
            'name' => $exception instanceof \ErrorException
                ? $this->errorTypeToString($exception->getSeverity())
                : $this->getClass($exception),
            'message' => $exception->getMessage()
        ];
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