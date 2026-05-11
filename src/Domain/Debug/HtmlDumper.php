<?php
declare(strict_types=1);
namespace Zodream\Debugger\Domain\Debug;

use Zodream\Infrastructure\Support\CodeBuilder;
use Zodream\Helpers\Html;

class HtmlDumper extends BaseDumper {
    /**
     * Colour definitions for output.
     *
     * @var array
     */
    protected array $styles = [
        'default' => 'background-color:#fff; color:#222; line-height:1.2em; font-weight:normal; font:12px Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:100000',
        'num' => 'color:#a71d5d',
        'const' => 'color:#795da3',
        'str' => 'color:#df5000',
        'cchr' => 'color:#222',
        'note' => 'color:#a71d5d',
        'ref' => 'color:#a0a0a0',
        'public' => 'color:#795da3',
        'protected' => 'color:#795da3',
        'private' => 'color:#795da3',
        'meta' => 'color:#b729d9',
        'key' => 'color:#df5000',
        'index' => 'color:#a71d5d',
    ];

    protected function encode(string $text): string {
        return Html::text($text);
    }

    public function write(CodeBuilder $builder, mixed $value): void {
        $builder->append('<pre class="zre-dump-container">');
        parent::write($builder, $value);
        $builder->append('</pre>');
    }

    protected function getColor(string $key): string {
        return isset($this->styles[$key]) ? $this->styles[$key] : '';
    }
    protected function writeWithColor(CodeBuilder $builder, string $color, string $text): void {
        $builder->append('<span style="'. $color .'">')->append($text)->append('</span>');
    }
}
