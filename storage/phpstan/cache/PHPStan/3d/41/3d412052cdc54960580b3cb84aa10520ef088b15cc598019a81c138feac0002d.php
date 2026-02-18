<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Events/Dispatcher.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Events\Dispatcher
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6387ef03d66c46aaf79a5fa19c7bde5db19de488777f5e680eab177922ea7100-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Events\\Dispatcher',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Events/Dispatcher.php',
      ),
    ),
    'namespace' => 'Illuminate\\Events',
    'name' => 'Illuminate\\Events\\Dispatcher',
    'shortName' => 'Dispatcher',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 885,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Events\\Dispatcher',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\Traits\\Macroable',
      1 => 'Illuminate\\Support\\Traits\\ReflectsClosures',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'container' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'container',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The IoC container instance.
 *
 * @var \\Illuminate\\Contracts\\Container\\Container
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'listeners' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'listeners',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 173,
            'startFilePos' => 1441,
            'endTokenPos' => 174,
            'endFilePos' => 1442,
          ),
        ),
        'docComment' => '/**
 * The registered event listeners.
 *
 * @var array<string, callable|array|class-string|null>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'wildcards' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'wildcards',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 185,
            'startFilePos' => 1570,
            'endTokenPos' => 186,
            'endFilePos' => 1571,
          ),
        ),
        'docComment' => '/**
 * The wildcard listeners.
 *
 * @var array<string, \\Closure|string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'wildcardsCache' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'wildcardsCache',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 197,
            'startFilePos' => 1711,
            'endTokenPos' => 198,
            'endFilePos' => 1712,
          ),
        ),
        'docComment' => '/**
 * The cached wildcard listeners.
 *
 * @var array<string, \\Closure|string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'queueResolver' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'queueResolver',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The queue resolver instance.
 *
 * @var callable(): \\Illuminate\\Contracts\\Queue\\Queue
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'transactionManagerResolver' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'transactionManagerResolver',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The database transaction manager resolver instance.
 *
 * @var callable
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 42,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'deferredEvents' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'deferredEvents',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 223,
            'startFilePos' => 2122,
            'endTokenPos' => 224,
            'endFilePos' => 2123,
          ),
        ),
        'docComment' => '/**
 * The currently deferred events.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'deferringEvents' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'deferringEvents',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 88,
            'endLine' => 88,
            'startTokenPos' => 235,
            'startFilePos' => 2247,
            'endTokenPos' => 235,
            'endFilePos' => 2251,
          ),
        ),
        'docComment' => '/**
 * Indicates if events should be deferred.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 5,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'eventsToDefer' => 
      array (
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'name' => 'eventsToDefer',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 95,
            'endLine' => 95,
            'startTokenPos' => 246,
            'startFilePos' => 2402,
            'endTokenPos' => 246,
            'endFilePos' => 2405,
          ),
        ),
        'docComment' => '/**
 * The specific events to defer (null means defer all events).
 *
 * @var string[]|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 95,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 36,
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
          'container' => 
          array (
            'name' => 'container',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 264,
                'startFilePos' => 2617,
                'endTokenPos' => 264,
                'endFilePos' => 2620,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Illuminate\\Contracts\\Container\\Container',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 33,
            'endColumn' => 68,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new event dispatcher instance.
 *
 * @param  \\Illuminate\\Contracts\\Container\\Container|null  $container
 */',
        'startLine' => 102,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
            'startLine' => 114,
            'endLine' => 114,
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
                'startLine' => 114,
                'endLine' => 114,
                'startTokenPos' => 302,
                'startFilePos' => 3022,
                'endTokenPos' => 302,
                'endFilePos' => 3025,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 114,
            'endLine' => 114,
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
 * @param  \\Illuminate\\Events\\QueuedClosure|callable|array|class-string|string  $events
 * @param  \\Illuminate\\Events\\QueuedClosure|callable|array|class-string|null  $listener
 * @return void
 */',
        'startLine' => 114,
        'endLine' => 137,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'setupWildcardListen' => 
      array (
        'name' => 'setupWildcardListen',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 44,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 52,
            'endColumn' => 60,
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
 * Setup a wildcard listener callback.
 *
 * @param  string  $event
 * @param  \\Closure|string  $listener
 * @return void
 */',
        'startLine' => 146,
        'endLine' => 151,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
            'startLine' => 159,
            'endLine' => 159,
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
        'startLine' => 159,
        'endLine' => 164,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'hasWildcardListeners' => 
      array (
        'name' => 'hasWildcardListeners',
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
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 42,
            'endColumn' => 51,
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
 * Determine if the given event has any wildcard listeners.
 *
 * @param  string  $eventName
 * @return bool
 */',
        'startLine' => 172,
        'endLine' => 181,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
            'startLine' => 190,
            'endLine' => 190,
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
                'startLine' => 190,
                'endLine' => 190,
                'startTokenPos' => 696,
                'startFilePos' => 5207,
                'endTokenPos' => 697,
                'endFilePos' => 5208,
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
 * Register an event and payload to be fired later.
 *
 * @param  string  $event
 * @param  object|array  $payload
 * @return void
 */',
        'startLine' => 190,
        'endLine' => 195,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
            'startLine' => 203,
            'endLine' => 203,
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
        'startLine' => 203,
        'endLine' => 206,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
            'startLine' => 214,
            'endLine' => 214,
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
        'startLine' => 214,
        'endLine' => 233,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'resolveSubscriber' => 
      array (
        'name' => 'resolveSubscriber',
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 42,
            'endColumn' => 52,
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
 * Resolve the subscriber instance.
 *
 * @param  object|class-string  $subscriber
 * @return $subscriber is object ? object : mixed
 */',
        'startLine' => 241,
        'endLine' => 248,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
            'startLine' => 257,
            'endLine' => 257,
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
                'startLine' => 257,
                'endLine' => 257,
                'startTokenPos' => 974,
                'startFilePos' => 6944,
                'endTokenPos' => 975,
                'endFilePos' => 6945,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 257,
            'endLine' => 257,
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
 * Fire an event until the first non-null response is returned.
 *
 * @param  string|object  $event
 * @param  mixed  $payload
 * @return array|null
 */',
        'startLine' => 257,
        'endLine' => 260,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
            'startLine' => 270,
            'endLine' => 270,
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
                'startLine' => 270,
                'endLine' => 270,
                'startTokenPos' => 1013,
                'startFilePos' => 7254,
                'endTokenPos' => 1014,
                'endFilePos' => 7255,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 270,
            'endLine' => 270,
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
                'startLine' => 270,
                'endLine' => 270,
                'startTokenPos' => 1021,
                'startFilePos' => 7266,
                'endTokenPos' => 1021,
                'endFilePos' => 7270,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 270,
            'endLine' => 270,
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
        'startLine' => 270,
        'endLine' => 300,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'invokeListeners' => 
      array (
        'name' => 'invokeListeners',
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
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 40,
            'endColumn' => 45,
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
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 48,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'halt' => 
          array (
            'name' => 'halt',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 310,
                'endLine' => 310,
                'startTokenPos' => 1213,
                'startFilePos' => 8783,
                'endTokenPos' => 1213,
                'endFilePos' => 8787,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 58,
            'endColumn' => 70,
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
 * Broadcast an event and call its listeners.
 *
 * @param  string|object  $event
 * @param  mixed  $payload
 * @param  bool  $halt
 * @return array|null
 */',
        'startLine' => 310,
        'endLine' => 339,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'parseEventAndPayload' => 
      array (
        'name' => 'parseEventAndPayload',
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 45,
            'endColumn' => 50,
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 53,
            'endColumn' => 60,
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
 * Parse the given event and payload and prepare them for dispatching.
 *
 * @param  mixed  $event
 * @param  mixed  $payload
 * @return array{string, array}
 */',
        'startLine' => 348,
        'endLine' => 355,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'shouldBroadcast' => 
      array (
        'name' => 'shouldBroadcast',
        'parameters' => 
        array (
          'payload' => 
          array (
            'name' => 'payload',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 40,
            'endColumn' => 53,
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
 * Determine if the payload has a broadcastable event.
 *
 * @param  array  $payload
 * @return bool
 */',
        'startLine' => 363,
        'endLine' => 368,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'broadcastWhen' => 
      array (
        'name' => 'broadcastWhen',
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
            'startLine' => 376,
            'endLine' => 376,
            'startColumn' => 38,
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
 * Check if the event should be broadcasted by the condition.
 *
 * @param  mixed  $event
 * @return bool
 */',
        'startLine' => 376,
        'endLine' => 381,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'broadcastEvent' => 
      array (
        'name' => 'broadcastEvent',
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
            'startLine' => 389,
            'endLine' => 389,
            'startColumn' => 39,
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
 * Broadcast the given event class.
 *
 * @param  \\Illuminate\\Contracts\\Broadcasting\\ShouldBroadcast  $event
 * @return void
 */',
        'startLine' => 389,
        'endLine' => 392,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'getListeners' => 
      array (
        'name' => 'getListeners',
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
            'startLine' => 400,
            'endLine' => 400,
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
 * Get all of the listeners for a given event name.
 *
 * @param  string  $eventName
 * @return array
 */',
        'startLine' => 400,
        'endLine' => 410,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'getWildcardListeners' => 
      array (
        'name' => 'getWildcardListeners',
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
            'startLine' => 418,
            'endLine' => 418,
            'startColumn' => 45,
            'endColumn' => 54,
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
 * Get the wildcard listeners for the event.
 *
 * @param  string  $eventName
 * @return array
 */',
        'startLine' => 418,
        'endLine' => 431,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'addInterfaceListeners' => 
      array (
        'name' => 'addInterfaceListeners',
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
            'startLine' => 440,
            'endLine' => 440,
            'startColumn' => 46,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'listeners' => 
          array (
            'name' => 'listeners',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 440,
                'endLine' => 440,
                'startTokenPos' => 1749,
                'startFilePos' => 12525,
                'endTokenPos' => 1750,
                'endFilePos' => 12526,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 440,
            'endLine' => 440,
            'startColumn' => 58,
            'endColumn' => 78,
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
 * Add the listeners for the event\'s interfaces to the given array.
 *
 * @param  string  $eventName
 * @param  array  $listeners
 * @return array
 */',
        'startLine' => 440,
        'endLine' => 451,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'prepareListeners' => 
      array (
        'name' => 'prepareListeners',
        'parameters' => 
        array (
          'eventName' => 
          array (
            'name' => 'eventName',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 459,
            'endLine' => 459,
            'startColumn' => 41,
            'endColumn' => 57,
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
 * Prepare the listeners for a given event.
 *
 * @param  string  $eventName
 * @return \\Closure[]
 */',
        'startLine' => 459,
        'endLine' => 468,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'makeListener' => 
      array (
        'name' => 'makeListener',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 477,
            'endLine' => 477,
            'startColumn' => 34,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'wildcard' => 
          array (
            'name' => 'wildcard',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 477,
                'endLine' => 477,
                'startTokenPos' => 1914,
                'startFilePos' => 13531,
                'endTokenPos' => 1914,
                'endFilePos' => 13535,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 477,
            'endLine' => 477,
            'startColumn' => 45,
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
 * Register an event listener with the dispatcher.
 *
 * @param  \\Closure|string|array{class-string, string}  $listener
 * @param  bool  $wildcard
 * @return \\Closure
 */',
        'startLine' => 477,
        'endLine' => 494,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'createClassListener' => 
      array (
        'name' => 'createClassListener',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 503,
            'endLine' => 503,
            'startColumn' => 41,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'wildcard' => 
          array (
            'name' => 'wildcard',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 503,
                'endLine' => 503,
                'startTokenPos' => 2066,
                'startFilePos' => 14304,
                'endTokenPos' => 2066,
                'endFilePos' => 14308,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 503,
            'endLine' => 503,
            'startColumn' => 52,
            'endColumn' => 68,
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
 * Create a class based listener using the IoC container.
 *
 * @param  string  $listener
 * @param  bool  $wildcard
 * @return \\Closure
 */',
        'startLine' => 503,
        'endLine' => 514,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'createClassCallable' => 
      array (
        'name' => 'createClassCallable',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 522,
            'endLine' => 522,
            'startColumn' => 44,
            'endColumn' => 52,
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
 * Create the class based event callable.
 *
 * @param  array{class-string, string}|string  $listener
 * @return callable
 */',
        'startLine' => 522,
        'endLine' => 541,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'parseClassCallable' => 
      array (
        'name' => 'parseClassCallable',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 549,
            'endLine' => 549,
            'startColumn' => 43,
            'endColumn' => 51,
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
 * Parse the class listener into class and method.
 *
 * @param  string  $listener
 * @return array{class-string, string}
 */',
        'startLine' => 549,
        'endLine' => 552,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'handlerShouldBeQueued' => 
      array (
        'name' => 'handlerShouldBeQueued',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 562,
            'endLine' => 562,
            'startColumn' => 46,
            'endColumn' => 51,
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
 * Determine if the event handler class should be queued.
 *
 * @param  class-string  $class
 * @return bool
 *
 * @phpstan-assert-if-true \\Illuminate\\Contracts\\Queue\\ShouldQueue $class
 */',
        'startLine' => 562,
        'endLine' => 571,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'createQueuedHandlerCallable' => 
      array (
        'name' => 'createQueuedHandlerCallable',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 580,
            'endLine' => 580,
            'startColumn' => 52,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 580,
            'endLine' => 580,
            'startColumn' => 60,
            'endColumn' => 66,
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
 * Create a callable for putting an event handler on the queue.
 *
 * @param  class-string  $class
 * @param  string  $method
 * @return \\Closure(): void
 */',
        'startLine' => 580,
        'endLine' => 591,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'handlerShouldBeDispatchedAfterDatabaseTransactions' => 
      array (
        'name' => 'handlerShouldBeDispatchedAfterDatabaseTransactions',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 599,
            'endLine' => 599,
            'startColumn' => 75,
            'endColumn' => 83,
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
 * Determine if the given event handler should be dispatched after all database transactions have committed.
 *
 * @param  mixed  $listener
 * @return bool
 */',
        'startLine' => 599,
        'endLine' => 604,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'createCallbackForListenerRunningAfterCommits' => 
      array (
        'name' => 'createCallbackForListenerRunningAfterCommits',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 613,
            'endLine' => 613,
            'startColumn' => 69,
            'endColumn' => 77,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 613,
            'endLine' => 613,
            'startColumn' => 80,
            'endColumn' => 86,
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
 * Create a callable for dispatching a listener after database transactions.
 *
 * @param  mixed  $listener
 * @param  string  $method
 * @return \\Closure
 */',
        'startLine' => 613,
        'endLine' => 624,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'handlerWantsToBeQueued' => 
      array (
        'name' => 'handlerWantsToBeQueued',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 633,
            'endLine' => 633,
            'startColumn' => 47,
            'endColumn' => 52,
            'parameterIndex' => 0,
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
            'startLine' => 633,
            'endLine' => 633,
            'startColumn' => 55,
            'endColumn' => 64,
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
 * Determine if the event handler wants to be queued.
 *
 * @param  class-string  $class
 * @param  array  $arguments
 * @return bool
 */',
        'startLine' => 633,
        'endLine' => 642,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'queueHandler' => 
      array (
        'name' => 'queueHandler',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 652,
            'endLine' => 652,
            'startColumn' => 37,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 652,
            'endLine' => 652,
            'startColumn' => 45,
            'endColumn' => 51,
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
            'startLine' => 652,
            'endLine' => 652,
            'startColumn' => 54,
            'endColumn' => 63,
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
 * Queue the handler class.
 *
 * @param  string  $class
 * @param  string  $method
 * @param  array  $arguments
 * @return void
 */',
        'startLine' => 652,
        'endLine' => 676,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'createListenerAndJob' => 
      array (
        'name' => 'createListenerAndJob',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 688,
            'endLine' => 688,
            'startColumn' => 45,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 688,
            'endLine' => 688,
            'startColumn' => 53,
            'endColumn' => 59,
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
            'startLine' => 688,
            'endLine' => 688,
            'startColumn' => 62,
            'endColumn' => 71,
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
 * Create the listener and job for a queued listener.
 *
 * @template TListener
 *
 * @param  class-string<TListener>  $class
 * @param  string  $method
 * @param  array  $arguments
 * @return array{TListener, mixed}
 */',
        'startLine' => 688,
        'endLine' => 695,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'propagateListenerOptions' => 
      array (
        'name' => 'propagateListenerOptions',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 704,
            'endLine' => 704,
            'startColumn' => 49,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'job' => 
          array (
            'name' => 'job',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 704,
            'endLine' => 704,
            'startColumn' => 60,
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
 * Propagate listener options to the job.
 *
 * @param  mixed  $listener
 * @param  \\Illuminate\\Events\\CallQueuedListener  $job
 * @return \\Illuminate\\Events\\CallQueuedListener
 */',
        'startLine' => 704,
        'endLine' => 746,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
            'startLine' => 754,
            'endLine' => 754,
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
        'startLine' => 754,
        'endLine' => 767,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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
 * Forget all of the pushed listeners.
 *
 * @return void
 */',
        'startLine' => 774,
        'endLine' => 781,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'resolveQueue' => 
      array (
        'name' => 'resolveQueue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the queue implementation from the resolver.
 *
 * @return \\Illuminate\\Contracts\\Queue\\Queue
 */',
        'startLine' => 788,
        'endLine' => 791,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'setQueueResolver' => 
      array (
        'name' => 'setQueueResolver',
        'parameters' => 
        array (
          'resolver' => 
          array (
            'name' => 'resolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 799,
            'endLine' => 799,
            'startColumn' => 38,
            'endColumn' => 55,
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
 * Set the queue resolver implementation.
 *
 * @param  callable(): \\Illuminate\\Contracts\\Queue\\Queue  $resolver
 * @return $this
 */',
        'startLine' => 799,
        'endLine' => 804,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'resolveTransactionManager' => 
      array (
        'name' => 'resolveTransactionManager',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the database transaction manager implementation from the resolver.
 *
 * @return \\Illuminate\\Database\\DatabaseTransactionsManager|null
 */',
        'startLine' => 811,
        'endLine' => 814,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'setTransactionManagerResolver' => 
      array (
        'name' => 'setTransactionManagerResolver',
        'parameters' => 
        array (
          'resolver' => 
          array (
            'name' => 'resolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 822,
            'endLine' => 822,
            'startColumn' => 51,
            'endColumn' => 68,
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
 * Set the database transaction manager resolver implementation.
 *
 * @param  (callable(): \\Illuminate\\Database\\DatabaseTransactionsManager|null)  $resolver
 * @return $this
 */',
        'startLine' => 822,
        'endLine' => 827,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'defer' => 
      array (
        'name' => 'defer',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 838,
            'endLine' => 838,
            'startColumn' => 27,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'events' => 
          array (
            'name' => 'events',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 838,
                'endLine' => 838,
                'startTokenPos' => 3869,
                'startFilePos' => 25067,
                'endTokenPos' => 3869,
                'endFilePos' => 25070,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'array',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 838,
            'endLine' => 838,
            'startColumn' => 47,
            'endColumn' => 67,
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
 * Execute the given callback while deferring events, then dispatch all deferred events.
 *
 * @template TResult
 *
 * @param  callable(): TResult  $callback
 * @param  string[]|null  $events
 * @return TResult
 */',
        'startLine' => 838,
        'endLine' => 863,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'shouldDeferEvent' => 
      array (
        'name' => 'shouldDeferEvent',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 871,
            'endLine' => 871,
            'startColumn' => 41,
            'endColumn' => 53,
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
 * Determine if the given event should be deferred.
 *
 * @param  string  $event
 * @return bool
 */',
        'startLine' => 871,
        'endLine' => 874,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
        'aliasName' => NULL,
      ),
      'getRawListeners' => 
      array (
        'name' => 'getRawListeners',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the raw, unprepared listeners.
 *
 * @return array
 */',
        'startLine' => 881,
        'endLine' => 884,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Events',
        'declaringClassName' => 'Illuminate\\Events\\Dispatcher',
        'implementingClassName' => 'Illuminate\\Events\\Dispatcher',
        'currentClassName' => 'Illuminate\\Events\\Dispatcher',
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