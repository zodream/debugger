<?php
declare(strict_types=1);
namespace Zodream\Debugger\Domain\Debug;

use Throwable;
use Zodream\Infrastructure\Support\CodeBuilder;
use Zodream\Database\Model\Model;
use ReflectionClass;
use ReflectionFunction;
use ReflectionProperty;
use ReflectionMethod;
use ReflectionEnum;

abstract class BaseDumper implements IDumper {
    private array $objRefs = [];

    public function dump(mixed $value): string {
        $builder = new CodeBuilder();
        $this->write($builder, $value);
        $res = (string)$builder;
        $builder->close();
        return $res;
    }

    public function write(CodeBuilder $builder, mixed $value): void {
        $this->objRefs = [];
        $this->writeInternal($builder, $value);
    }


    protected abstract function getColor(string $mark): string;
    protected abstract function writeWithColor(CodeBuilder $builder, string $color, string $text): void;
    protected function writeWithMark(CodeBuilder $builder, string $mark, string $text): void {
        $this->writeWithColor($builder, $this->getColor($mark), $text);
    }

    protected function writeInternal(CodeBuilder $builder, mixed $value): void {
        switch (true) {
            case is_null($value):
                $this->writeNull($builder);
                break;
            case is_bool($value):
                $this->writeBoolean($builder, $value);
                break;
            case is_string($value):
                $this->writeString($builder, $value);
                break;
            case is_int($value):
            case is_double($value):
            case is_float($value):
                $this->writeNumeric($builder, $value);
                break;
            case is_array($value):
                $this->writeArray($builder, $value);
                break;
            case is_object($value):
                $guid = spl_object_id($value);
                if (in_array($guid, $this->objRefs) || $this->isHiddenObject($value)) {
                    $this->writeWithMark($builder, 'ref', sprintf('%s {#%d}', get_class($value), $guid));
                    break;
                }
                $this->objRefs[] = $guid;
                $value instanceof Throwable ? $this->writeException($builder, $value) : $this->writeClass($builder, $value);
                break;
            case $value instanceof \Closure:
                $this->writeClosure($builder, $value);
                break;
            default: // resource
                $this->writeResource($builder, $value);
                break;
        }
    }

    /**
     * 隐藏一些嵌套深的系统组件
     */
    protected function isHiddenObject(mixed $value): bool {
        return $value instanceof \Zodream\Infrastructure\Contracts\Application 
        || $value instanceof \Zodream\Infrastructure\Contracts\HttpContext
        || $value instanceof \Zodream\Infrastructure\Contracts\Http\Input
        || $value instanceof \Zodream\Infrastructure\Contracts\Http\HttpOutput
        || $value instanceof \Zodream\Infrastructure\Contracts\Router
        || $value instanceof \Zodream\Infrastructure\Contracts\Database;
    }

    private function writeBoolean(CodeBuilder $builder, bool $value): void {
        $this->writeWithMark($builder, 'index', $value ? 'true' : 'false');
    }

    private function writeNull(CodeBuilder $builder): void {
        $this->writeWithMark($builder, 'index', 'null');
    }

    private function writeNumeric(CodeBuilder $builder, int|float $value): void {
        $this->writeWithMark($builder, 'num', print_r($value, true));
    }

    private function writeString(CodeBuilder $builder, string $value): void {
        $this->writeWithMark($builder, 'str', var_export($value, true));
    }



    private function writeArray(CodeBuilder $builder, array $value): void {
        $len = count($value);
        if ($len === 0) {
            $this->writeWithMark($builder, 'ref', '[]');
            return;
        }
        $isAssoc = !array_is_list($value);
        $this->writeWithMark($builder, 'index', 'array:'.$len);
        $builder->append(' [')->appendIndentLine();
        $first = true;
        foreach($value as $key => $val) {
            if (!$first) {
                $builder->append(',')->appendLine();
            }
            if (!$isAssoc) {
                $this->writeInternal($builder, $val);
            } else {
                $this->writeInternal($builder, $key);
                $builder->append(' => ');
                $this->writeInternal($builder, $val);
            }
            $first = false;
        }
        $builder->appendOutdentLine()->append(']');
    }

    private function writeResource(CodeBuilder $builder, mixed $value): void {
        $cls = @get_resource_type($value);
        $this->writeWithMark($builder, 'ref', sprintf('{resource#%s}', $cls));
    }

    private function writeException(CodeBuilder $builder, Throwable $value): void {
        $this->writeWithMark($builder, 'index', get_class($value));
        $builder->append(' {')->appendIndentLine()
        ->append('#message: ');
        $this->writeInternal($builder, $value->getMessage());
        $builder->appendLine()->append('#file: ');
        $this->writeInternal($builder, $value->getFile());
        $builder->appendLine()->append('#line: ');
        $this->writeInternal($builder, $value->getLine());
        
        $items = $value->getTrace();
        if (!empty($items)) {
            $builder->appendLine()->append('#trace: ');
            $this->writeInternal($builder, $items);
        }
        $builder->appendOutdentLine()->append('}');
    }

    private function writeClosure(CodeBuilder $builder, \Closure $value): void {
        $this->writeWithMark($builder, 'ref', '{Closure}');
    }

    private function writeClass(CodeBuilder $builder, mixed $value): void {
        if ($value instanceof Model) {
            $this->writeEntity($builder, $value);
            return;
        }
        $ref = new ReflectionClass($value);
        if ($ref->isEnum()) {
            $this->writeEnum($builder, $value);
            return;
        }
        $this->writeWithMark($builder, 'index', get_class($value));
        $builder->append(' {');
        $properties = $ref->getProperties();
        if (count($properties) === 0) {
            $builder->append('}');
            return;
        }
        $builder->appendIndentLine();
        $first = true;
        foreach($properties as $prop) {
            if (!$first) {
                $builder->append(',')->appendLine();
            }
            $this->writeProperty($builder, $value, $prop);
            $first = false;
        }
        $builder->appendOutdentLine()->append('}');
    }

    private function writeEntity(CodeBuilder $builder, Model $value): void {
        $ref = new ReflectionClass($value);
        $this->writeWithMark($builder, 'index', get_class($value));
        $builder->append(' {');
        $property = $ref->getProperty('__attributes');
        if (empty($property)) {
            $builder->append('}');
            return;
        }
        $builder->appendIndentLine();
        $this->writeProperty($builder, $value, $property);
        $builder->appendOutdentLine()->append('}');
    }

    private function writeEnum(CodeBuilder $builder, mixed $value): void {
        $ref = new ReflectionEnum($value);
        $builder->append($ref->getName())->append('::')->append($value->name);
    }

    private function writeConst(CodeBuilder $builder, string $name, mixed $value): void {
        $builder->append('const ')->append($name)->append(' = ');
        $this->writeInternal($builder, $value);
    }

    private function writeProperty(CodeBuilder $builder, mixed $value, ReflectionProperty $prop): void {
        $tag = '-';
        if ($prop->isPublic()) {
            $tag = '+';
        } else if ($prop->isProtected()) {
            $tag = '#';
        }
        $this->writeWithMark($builder, 'protected', $tag.$prop->getName());
        $builder->append(': ');
        $this->writeInternal($builder, $prop->getValue($value));
    }

    private function writeFunction(CodeBuilder $builder, ReflectionFunction $value): void {
        $builder->append('{Function}');
    }

    private function writeMethod(CodeBuilder $builder, ReflectionMethod $value): void {
        $builder->append('{Method}');
    }
}