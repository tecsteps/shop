<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Support/Testing/Fakes/EventFake.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Support\Testing\Fakes\EventFake
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-5bf03a0ca129e5e91734f5e2e839d726cd0c8235489a757d6b4e303b914ecb8c-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Support/Testing/Fakes/EventFake.php',
      ),
    ),
    'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
    'name' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
    'shortName' => 'EventFake',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 453,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Events\\Dispatcher',
      1 => 'Illuminate\\Support\\Testing\\Fakes\\Fake',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\Traits\\ForwardsCalls',
      1 => 'Illuminate\\Support\\Traits\\ReflectsClosures',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'dispatcher' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'name' => 'dispatcher',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The original event dispatcher.
 *
 * @var \\Illuminate\\Contracts\\Events\\Dispatcher
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'eventsToFake' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'name' => 'eventsToFake',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 102,
            'startFilePos' => 824,
            'endTokenPos' => 103,
            'endFilePos' => 825,
          ),
        ),
        'docComment' => '/**
 * The event types that should be intercepted instead of dispatched.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'eventsToDispatch' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'name' => 'eventsToDispatch',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 114,
            'startFilePos' => 977,
            'endTokenPos' => 115,
            'endFilePos' => 978,
          ),
        ),
        'docComment' => '/**
 * The event types that should be dispatched instead of intercepted.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'events' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'name' => 'events',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 126,
            'startFilePos' => 1114,
            'endTokenPos' => 127,
            'endFilePos' => 1115,
          ),
        ),
        'docComment' => '/**
 * All of the events that have been intercepted keyed by type.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'dispatcher' => 
          array (
            'name' => 'dispatcher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Events\\Dispatcher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 33,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'eventsToFake' => 
          array (
            'name' => 'eventsToFake',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 55,
                'endLine' => 55,
                'startTokenPos' => 147,
                'startFilePos' => 1366,
                'endTokenPos' => 148,
                'endFilePos' => 1367,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 57,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new event fake instance.
 *
 * @param  \\Illuminate\\Contracts\\Events\\Dispatcher  $dispatcher
 * @param  array|string  $eventsToFake
 */',
        'startLine' => 55,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'except' => 
      array (
        'name' => 'except',
        'parameters' => 
        array (
          'eventsToDispatch' => 
          array (
            'name' => 'eventsToDispatch',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 28,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Specify the events that should be dispatched instead of faked.
 *
 * @param  array|string  $eventsToDispatch
 * @return $this
 */',
        'startLine' => 68,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'assertListening' => 
      array (
        'name' => 'assertListening',
        'parameters' => 
        array (
          'expectedEvent' => 
          array (
            'name' => 'expectedEvent',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedListener' => 
          array (
            'name' => 'expectedListener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 53,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Assert if an event has a listener attached to it.
 *
 * @param  string  $expectedEvent
 * @param  string|array  $expectedListener
 * @return void
 */',
        'startLine' => 85,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'assertDispatched' => 
      array (
        'name' => 'assertDispatched',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 38,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 134,
                'endLine' => 134,
                'startTokenPos' => 495,
                'startFilePos' => 3832,
                'endTokenPos' => 495,
                'endFilePos' => 3835,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 46,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Assert if an event was dispatched based on a truth-test callback.
 *
 * @param  string|\\Closure  $event
 * @param  callable|int|null  $callback
 * @return void
 */',
        'startLine' => 134,
        'endLine' => 148,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'assertDispatchedOnce' => 
      array (
        'name' => 'assertDispatchedOnce',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 42,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Assert if an event was dispatched exactly once.
 *
 * @param  string  $event
 * @param  int  $times
 * @return void
 */',
        'startLine' => 157,
        'endLine' => 160,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'assertDispatchedTimes' => 
      array (
        'name' => 'assertDispatchedTimes',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 43,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'times' => 
          array (
            'name' => 'times',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 169,
                'endLine' => 169,
                'startTokenPos' => 640,
                'startFilePos' => 4753,
                'endTokenPos' => 640,
                'endFilePos' => 4753,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 51,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Assert if an event was dispatched a number of times.
 *
 * @param  string  $event
 * @param  int  $times
 * @return void
 */',
        'startLine' => 169,
        'endLine' => 181,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'assertNotDispatched' => 
      array (
        'name' => 'assertNotDispatched',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 41,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 190,
                'endLine' => 190,
                'startTokenPos' => 735,
                'startFilePos' => 5373,
                'endTokenPos' => 735,
                'endFilePos' => 5376,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 49,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if an event was dispatched based on a truth-test callback.
 *
 * @param  string|\\Closure  $event
 * @param  callable|null  $callback
 * @return void
 */',
        'startLine' => 190,
        'endLine' => 200,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'assertNothingDispatched' => 
      array (
        'name' => 'assertNothingDispatched',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Assert that no events were dispatched.
 *
 * @return void
 */',
        'startLine' => 207,
        'endLine' => 224,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'dispatched' => 
      array (
        'name' => 'dispatched',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 32,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 233,
                'endLine' => 233,
                'startTokenPos' => 945,
                'startFilePos' => 6584,
                'endTokenPos' => 945,
                'endFilePos' => 6587,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 40,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all of the events matching a truth-test callback.
 *
 * @param  string  $event
 * @param  callable|null  $callback
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 233,
        'endLine' => 244,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'hasDispatched' => 
      array (
        'name' => 'hasDispatched',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 252,
            'endLine' => 252,
            'startColumn' => 35,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the given event has been dispatched.
 *
 * @param  string  $event
 * @return bool
 */',
        'startLine' => 252,
        'endLine' => 255,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'listen' => 
      array (
        'name' => 'listen',
        'parameters' => 
        array (
          'events' => 
          array (
            'name' => 'events',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 264,
            'endLine' => 264,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'listener' => 
          array (
            'name' => 'listener',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 264,
                'endLine' => 264,
                'startTokenPos' => 1088,
                'startFilePos' => 7363,
                'endTokenPos' => 1088,
                'endFilePos' => 7366,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 264,
            'endLine' => 264,
            'startColumn' => 37,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Register an event listener with the dispatcher.
 *
 * @param  \\Closure|string|array  $events
 * @param  mixed  $listener
 * @return void
 */',
        'startLine' => 264,
        'endLine' => 267,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'hasListeners' => 
      array (
        'name' => 'hasListeners',
        'parameters' => 
        array (
          'eventName' => 
          array (
            'name' => 'eventName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 275,
            'endLine' => 275,
            'startColumn' => 34,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if a given event has listeners.
 *
 * @param  string  $eventName
 * @return bool
 */',
        'startLine' => 275,
        'endLine' => 278,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'push' => 
      array (
        'name' => 'push',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 26,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'payload' => 
          array (
            'name' => 'payload',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 287,
                'endLine' => 287,
                'startTokenPos' => 1150,
                'startFilePos' => 7890,
                'endTokenPos' => 1151,
                'endFilePos' => 7891,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 34,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Register an event and payload to be dispatched later.
 *
 * @param  string  $event
 * @param  array  $payload
 * @return void
 */',
        'startLine' => 287,
        'endLine' => 290,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'subscribe' => 
      array (
        'name' => 'subscribe',
        'parameters' => 
        array (
          'subscriber' => 
          array (
            'name' => 'subscriber',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 298,
            'endLine' => 298,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Register an event subscriber with the dispatcher.
 *
 * @param  object|string  $subscriber
 * @return void
 */',
        'startLine' => 298,
        'endLine' => 301,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'flush' => 
      array (
        'name' => 'flush',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 27,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush a set of pushed events.
 *
 * @param  string  $event
 * @return void
 */',
        'startLine' => 309,
        'endLine' => 312,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'dispatch' => 
      array (
        'name' => 'dispatch',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 322,
            'endLine' => 322,
            'startColumn' => 30,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'payload' => 
          array (
            'name' => 'payload',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 322,
                'endLine' => 322,
                'startTokenPos' => 1217,
                'startFilePos' => 8572,
                'endTokenPos' => 1218,
                'endFilePos' => 8573,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 322,
            'endLine' => 322,
            'startColumn' => 38,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'halt' => 
          array (
            'name' => 'halt',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 322,
                'endLine' => 322,
                'startTokenPos' => 1225,
                'startFilePos' => 8584,
                'endTokenPos' => 1225,
                'endFilePos' => 8588,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 322,
            'endLine' => 322,
            'startColumn' => 53,
            'endColumn' => 65,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fire an event and call the listeners.
 *
 * @param  string|object  $event
 * @param  mixed  $payload
 * @param  bool  $halt
 * @return array|null
 */',
        'startLine' => 322,
        'endLine' => 331,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'shouldFakeEvent' => 
      array (
        'name' => 'shouldFakeEvent',
        'parameters' => 
        array (
          'eventName' => 
          array (
            'name' => 'eventName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 340,
            'endLine' => 340,
            'startColumn' => 40,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'payload' => 
          array (
            'name' => 'payload',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 340,
            'endLine' => 340,
            'startColumn' => 52,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if an event should be faked or actually dispatched.
 *
 * @param  string  $eventName
 * @param  mixed  $payload
 * @return bool
 */',
        'startLine' => 340,
        'endLine' => 357,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'fakeEvent' => 
      array (
        'name' => 'fakeEvent',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 367,
            'endLine' => 367,
            'startColumn' => 34,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 367,
            'endLine' => 367,
            'startColumn' => 42,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'arguments' => 
          array (
            'name' => 'arguments',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 367,
            'endLine' => 367,
            'startColumn' => 49,
            'endColumn' => 58,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Push the event onto the fake events array immediately or after the next database transaction.
 *
 * @param  string|object  $event
 * @param  string  $name
 * @param  array  $arguments
 * @return void
 */',
        'startLine' => 367,
        'endLine' => 375,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'shouldDispatchEvent' => 
      array (
        'name' => 'shouldDispatchEvent',
        'parameters' => 
        array (
          'eventName' => 
          array (
            'name' => 'eventName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 44,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'payload' => 
          array (
            'name' => 'payload',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 56,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine whether an event should be dispatched or not.
 *
 * @param  string  $eventName
 * @param  mixed  $payload
 * @return bool
 */',
        'startLine' => 384,
        'endLine' => 397,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'forget' => 
      array (
        'name' => 'forget',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 405,
            'endLine' => 405,
            'startColumn' => 28,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove a set of listeners from the dispatcher.
 *
 * @param  string  $event
 * @return void
 */',
        'startLine' => 405,
        'endLine' => 408,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'forgetPushed' => 
      array (
        'name' => 'forgetPushed',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Forget all of the queued listeners.
 *
 * @return void
 */',
        'startLine' => 415,
        'endLine' => 418,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'until' => 
      array (
        'name' => 'until',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 427,
            'endLine' => 427,
            'startColumn' => 27,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'payload' => 
          array (
            'name' => 'payload',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 427,
                'endLine' => 427,
                'startTokenPos' => 1698,
                'startFilePos' => 11447,
                'endTokenPos' => 1699,
                'endFilePos' => 11448,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 427,
            'endLine' => 427,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Dispatch an event and call the listeners.
 *
 * @param  string|object  $event
 * @param  mixed  $payload
 * @return mixed
 */',
        'startLine' => 427,
        'endLine' => 430,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      'dispatchedEvents' => 
      array (
        'name' => 'dispatchedEvents',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the events that have been dispatched.
 *
 * @return array
 */',
        'startLine' => 437,
        'endLine' => 440,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
      '__call' => 
      array (
        'name' => '__call',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 449,
            'endLine' => 449,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 449,
            'endLine' => 449,
            'startColumn' => 37,
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Handle dynamic method calls to the dispatcher.
 *
 * @param  string  $method
 * @param  array  $parameters
 * @return mixed
 */',
        'startLine' => 449,
        'endLine' => 452,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Testing\\Fakes',
        'declaringClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'implementingClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'currentClassName' => 'Illuminate\\Support\\Testing\\Fakes\\EventFake',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));