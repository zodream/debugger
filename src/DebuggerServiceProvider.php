<?php
declare(strict_types=1);
namespace Zodream\Debugger;

use Zodream\Debugger\Domain\Timer;
use Zodream\Infrastructure\Support\ServiceProvider;

class DebuggerServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->singletonIf(Debugger::class);
        $this->app->singletonIf(Timer::class);
        $this->app->alias(Debugger::class, 'debugger');
        $this->app->alias(Timer::class, 'timer');
        $this->app->make('debugger');
    }
}