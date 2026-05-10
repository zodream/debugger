<?php
declare(strict_types=1);
namespace Zodream\Debugger\Domain\Debug;

use Zodream\Infrastructure\Support\CodeBuilder;

class RawDumper extends BaseDumper {

    protected function getColor(string $key): string {
        return '';
    }
    protected function writeWithColor(CodeBuilder $builder, string $color, string $text): void {
        $builder->append($text);
    }
}