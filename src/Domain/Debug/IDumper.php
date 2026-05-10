<?php
declare(strict_types=1);
namespace Zodream\Debugger\Domain\Debug;

use Zodream\Infrastructure\Support\CodeBuilder;

interface IDumper {


    public function dump(mixed $value): string;
    public function write(CodeBuilder $builder, mixed $value): void;
}