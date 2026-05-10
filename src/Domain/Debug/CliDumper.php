<?php
declare(strict_types=1);
namespace Zodream\Debugger\Domain\Debug;

use Zodream\Infrastructure\Support\CodeBuilder;
use Zodream\Debugger\Domain\Console;

class CliDumper extends BaseDumper {

    protected array $styles = [
        // See http://en.wikipedia.org/wiki/ANSI_escape_code#graphics
        'default' => '0;38;5;208',
        'num' => '1;38;5;38',
        'const' => '1;38;5;208',
        'virtual' => '3',
        'str' => '1;38;5;113',
        'note' => '38;5;38',
        'ref' => '38;5;247',
        'public' => '39',
        'protected' => '39',
        'private' => '39',
        'meta' => '38;5;170',
        'key' => '38;5;113',
        'index' => '38;5;38',
    ];

    protected function getColor(string $key): string {
        return isset($this->styles[$key]) ? $this->styles[$key] : '';
    }
    protected function writeWithColor(CodeBuilder $builder, string $color, string $text): void {
        $hasColor = !empty($color) && $color !== '0';
        if ($hasColor) {
            $builder->appendFormat("\e[%sm", $color);
        }
        $builder->append($text);
        if ($hasColor) {
            $builder->append(Console::COLOR_DEFAULT);
        }
    }

}