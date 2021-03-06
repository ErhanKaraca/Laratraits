<?php

namespace DarkGhostHunter\Laratraits\Tests;

use LogicException;
use JsonSerializable;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Htmlable;
use DarkGhostHunter\Laratraits\SavesToSession;
use Illuminate\Contracts\Session\Session as SessionContract;

class SavesToSessionTest extends TestCase
{
    public function testSavesToSession()
    {
        $sessionable = new class() {
            use SavesToSession;

            public function toSession()
            {
                return 'bar';
            }
        };

        $sessionable->saveToSession('foo');

        $this->assertEquals('bar', Session::get('foo'));
    }

    public function testSavesJsonable()
    {
        $sessionable = new class() implements Jsonable {
            use SavesToSession;

            /**
             * @inheritDoc
             */
            public function toJson($options = 0)
            {
                return '{"foo":"bar"}';
            }
        };

        $sessionable->saveToSession('foo');

        $this->assertEquals('{"foo":"bar"}', Session::get('foo'));
    }

    public function testSavesJsonSerializable()
    {
        $sessionable = new class() implements JsonSerializable {
            use SavesToSession;

            public function jsonSerialize()
            {
                return ['foo' => 'bar'];
            }
        };

        $sessionable->saveToSession('foo');

        $this->assertEquals('{"foo":"bar"}', Session::get('foo'));
    }

    public function testSavesHtmlable()
    {
        $sessionable = new class() implements Htmlable {
            use SavesToSession;

            public function toHtml()
            {
                return 'bar';
            }
        };

        $sessionable->saveToSession('foo');

        $this->assertEquals('bar', Session::get('foo'));
    }

    public function testSavesStringable()
    {
        $sessionable = new class() {
            use SavesToSession;

            public function __toString()
            {
                return 'bar';
            }
        };

        $sessionable->saveToSession('foo');

        $this->assertEquals('bar', Session::get('foo'));
    }

    public function testSavesObjectInstance()
    {
        $session = new class implements SessionContract {
            public static $used = false;
            public function getName(){}
            public function getId(){}
            public function setId($id){}
            public function start(){}
            public function save(){}
            public function all(){}
            public function exists($key){}
            public function has($key){}
            public function get($key, $default = null){}
            public function put($key, $value = null){
                self::$used = true;
            }
            public function token(){}
            public function remove($key){}
            public function forget($keys){}
            public function flush(){}
            public function migrate($destroy = false){}
            public function isStarted(){}
            public function previousUrl(){}
            public function setPreviousUrl($url){}
            public function getHandler(){}
            public function handlerNeedsRequest(){}
            public function setRequestOnHandler($request){}
        };

        $session = $this->app->instance(SessionContract::class, $session);

        $sessionable = new class() {
            use SavesToSession;
        };

        $sessionable->saveToSession('foo');

        $this->assertTrue($session::$used);
    }

    public function testSavesWithDefaultSessionKey()
    {
        $sessionable = new class() {
            use SavesToSession;

            protected function defaultSessionKey()
            {
                return 'foo';
            }

            public function __toString()
            {
                return 'bar';
            }
        };

        $sessionable->saveToSession();

        $this->assertEquals('bar', Session::get('foo'));
    }

    public function testExceptionWhenNoSessionKey()
    {
        $this->expectException(LogicException::class);

        $sessionable = new class() {
            use SavesToSession;
            public function __toString()
            {
                return 'bar';
            }
        };

        $sessionable->saveToSession();
    }
}
