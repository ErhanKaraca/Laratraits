<?php

namespace Tests;

use Illuminate\Pipeline\Pipeline;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Bus;
use DarkGhostHunter\Laratraits\PipesThrough;
use Illuminate\Contracts\Container\Container;
use DarkGhostHunter\Laratraits\Jobs\DispatchablePipeline;

class PipesThroughTest extends TestCase
{
    public function testPipesThroughDefaultPipelines()
    {
        $pipes = new class() {
            use PipesThrough;
            public $foo;
        };

        $pipe = function ($object, $next) {
            $object->foo = 'bar';
            return $next($object);
        };

        $this->assertEquals('bar', $pipes->pipe($pipe)->foo);
    }

    public function testPipesCustomPipeline()
    {
        $pipeline = new class() extends Pipeline {
            public function __construct(Container $container = null)
            {
                parent::__construct($container);

                $this->pipes[] = function ($object, $next) {
                    $object->foo = 'bar';
                    return $next($object);
                };
            }
        };

        $pipes = new class($pipeline)  {
            use PipesThrough;
            public $foo;
            public $pipeline;
            public function __construct($pipeline)
            {
                $this->pipeline = $pipeline;
            }
            protected function makePipeline() : \Illuminate\Contracts\Pipeline\Pipeline
            {
                return $this->pipeline;
            }
        };

        $this->assertEquals('bar', $pipes->pipe()->foo);
    }

    public function testPipesToClosureDestination()
    {
        $pipes = new class() {
            use PipesThrough;
            public $foo;
        };

        $pipe = function ($object, $next) {
            $object->foo = 'bar';
            return $next($object);
        };

        $destination = function ($passable) {
            $passable->foo = 'quz';
            return $passable;
        };

        $this->assertEquals('quz', $pipes->pipe($pipe, $destination)->foo);
    }

    public function testDispatchesToQueue()
    {
        $bus = Bus::fake();

        $pipes = new class() {
            use PipesThrough;
            public $foo;
        };

        $pipes->dispatchPipeline();

        $bus->assertDispatched(DispatchablePipeline::class);
    }

    public function testDispatchesToQueueWithPipes()
    {
        $bus = Bus::fake();

        $pipes = new class() {
            use PipesThrough;
            public $foo;
        };

        $done = false;

        $pipes->dispatchPipeline([
            function ($thing, $next) use (&$done) {
                $done = true;
                return $next($thing);
            }
        ]);

        $bus->assertDispatched(DispatchablePipeline::class, function ($job) {
            $job->handle();
            return true;
        });

        $this->assertTrue($done);
    }
}
