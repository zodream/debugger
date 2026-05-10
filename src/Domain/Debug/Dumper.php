<?php
declare(strict_types=1);
namespace Zodream\Debugger\Domain\Debug;

use Throwable;
use Zodream\Infrastructure\Support\CodeBuilder;

class Dumper {
    /**
     * Dump a value with elegance.
     *
     * @param  mixed[] $args
     * @return void
     */
    public static function dump(...$args) : void {
        $builder = new CodeBuilder();
        $isCli = request()->isCli();
        $dumper = $isCli ? new CliDumper() : new HtmlDumper();
        foreach ($args as $x) {
            $dumper->write($builder, $x);
            $builder->appendLine();
        }
        $response = response();
        if ($isCli) {
            $response->str((string)$builder);
        } else {
            $response->html((string)$builder);
        }
        $builder->close();
        $response->send();
        die(1);
    }

    public static function dumpException(Throwable $ex): array {
        $info = static::formatException($ex);
        $info['trace'] = $ex->getTrace();
        return $info;
    }

    public static function print(mixed $value): string {
        return (new RawDumper())->dump($value);
    }

    protected static function formatException(Throwable $ex): array {
        return  [
            'message' => htmlspecialchars($ex->getMessage()),
            'file' => $ex->getFile(),
            'line' => $ex->getLine()
        ];
    }
}
