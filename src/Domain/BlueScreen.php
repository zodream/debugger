<?php
namespace Zodream\Debugger\Domain;


use Zodream\Disk\FileSystem;
use Zodream\Helpers\Html;
use Zodream\Helpers\Json;
use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Template\ViewFactory;

class BlueScreen extends BaseBox {

    public function render($exception): Output {
        $base_dir = dirname(__DIR__).'/UserInterface/';
        $response = response()->statusCode(400)->allowCors();
        $view = new ViewFactory();
        if (!app()->isDebug()) {
            return $response->html($view->render($base_dir.'Error/404.php'));
        }
        $info = $this->getInfo($exception);
        $exceptions = $this->getAllException($exception);
        if (request()->wantsJson() || request()->isJson()) {
            return $response->json($info);
        }
        return $response->html($view->render($base_dir.'Home/index.php', compact('info', 'exceptions')));
    }

    protected function getInfo($exception) {
        return $this->formatException($exception);
    }

    protected function formatException($exception) {
        return [
            'name' => $exception instanceof \ErrorException
                ? $this->errorTypeToString($exception->getSeverity())
                : $this->getClass($exception),
            'message' => htmlspecialchars($exception->getMessage()),
            'file' => $this->getRelative($exception->getFile()),
            'line' => $exception->getLine()
        ];
    }

    protected function getRelative($file) {
        return FileSystem::relativePath(app_path(), $file);
    }

    protected function formatTrace(array $traces) {
        foreach ($traces as &$trace) {
            $trace['args'] = $this->formatParameter($trace);
            if (isset($trace['file']) && !empty($trace['file'])) {
                $trace['source'] = $this->formatSource($trace['file'], $trace['line']);
                $trace['file'] = $this->getRelative($trace['file']);
            }
        }
        return $traces;
    }

    protected function formatSource($file, $line) {
        return $this->highlightFile($file, $line);
    }

    protected function getAllException($exception) {
        $data = [];
        do {
            $info = $this->formatException($exception);
            $trace = $exception->getTrace();
            $info['trace'] = $this->formatTrace($trace);
            $info['source'] = $this->formatSource($info['file'], $info['line']);
            $data[] = $info;
        } while ($exception = $exception->getPrevious());
        return $data;
    }


    protected function formatParameter($trace) {
        if (!isset($trace['args'])) {
            return [];
        }
        try {
            $r = isset($trace['class'])
                ? new \ReflectionMethod($trace['class'], $trace['function'])
                : new \ReflectionFunction($trace['function']);
            $params = $r->getParameters();
        } catch (\Exception $e) {
            $params = [];
        }
        $data = [];
        foreach ($trace['args'] as $key => $value) {
            $name = isset($params[$key]) ? '$' . $params[$key]->name : "#$key";
            $data[$name] = Html::text(print_r($value, true));
        }
        return $data;
    }



    protected function getLastError($exception) {
        $lastError = $exception instanceof \ErrorException || $exception instanceof \Error ? null : error_get_last();
    }

    protected function getClass($obj) {
        return explode("\x00", get_class($obj))[0];
    }



    public function highlightFile($file, $line, $lines = 15) {
        $source = @file_get_contents($file); // @ file may not exist
        if ($source) {
            $source = $this->highlightPhp($source, $line, $lines);
            return $source;
        }
        return '';
    }


    /**
     * Returns syntax highlighted source code.
     * @param  string  $source
     * @param  int  $line
     * @param  int  $lines
     * @return string
     */
    public function highlightPhp($source, $line, $lines = 15) {
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#998; font-style: italic');
            ini_set('highlight.default', '#000');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#D24; font-weight: bold');
            ini_set('highlight.string', '#080');
        }

        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $source = explode("\n", highlight_string($source, true));
        $out = $source[0]; // <code><span color=highlight.html>
        $source = str_replace('<br />', "\n", $source[1]);
        $out .= $this->highlightLine($source, $line, $lines);
        $out = str_replace('&nbsp;', ' ', $out);
        return "<pre class='code'><div>$out</div></pre>";
    }


    /**
     * Returns highlighted line in HTML code.
     * @param $html
     * @param $line
     * @param int $lines
     * @return string
     */
    public function highlightLine($html, $line, $lines = 15) {
        $source = explode("\n", "\n" . str_replace("\r\n", "\n", $html));
        $out = '';
        $spans = 1;
        $start = $i = max(1, min($line, count($source) - 1) - (int) floor($lines * 2 / 3));
        while (--$i >= 1) { // find last highlighted block
            if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
                if ($m[1] !== '</span>') {
                    $spans++;
                    $out .= $m[1];
                }
                break;
            }
        }

        $source = array_slice($source, $start, $lines, true);
        end($source);
        $numWidth = strlen((string) key($source));

        foreach ($source as $n => $s) {
            $spans += substr_count($s, '<span') - substr_count($s, '</span');
            $s = str_replace(["\r", "\n"], ['', ''], $s);
            preg_match_all('#<[^>]+>#', $s, $tags);
            if ($n == $line) {
                $out .= sprintf(
                    "<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",
                    $n,
                    strip_tags($s),
                    implode('', $tags[0])
                );
            } else {
                $out .= sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n", $n, $s);
            }
        }
        $out .= str_repeat('</span>', $spans) . '</code>';
        return $out;
    }
}