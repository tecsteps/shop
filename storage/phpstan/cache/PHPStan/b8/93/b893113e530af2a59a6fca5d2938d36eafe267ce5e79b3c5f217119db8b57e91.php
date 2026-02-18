<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../monolog/monolog/src/Monolog/Logger.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Monolog\Logger
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-fe7a0a0a924ff3b2174c8b7b8decdf6691fb3daea0994b2b60a7218a93acdfaf-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Monolog\\Logger',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../monolog/monolog/src/Monolog/Logger.php',
      ),
    ),
    'namespace' => 'Monolog',
    'name' => 'Monolog\\Logger',
    'shortName' => 'Logger',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Monolog log channel
 *
 * It contains a stack of Handlers and a stack of Processors,
 * and uses them to store records that are added to it.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @final
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 35,
    'endLine' => 751,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Psr\\Log\\LoggerInterface',
      1 => 'Monolog\\ResettableInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'DEBUG' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'DEBUG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '100',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 96,
            'startFilePos' => 939,
            'endTokenPos' => 96,
            'endFilePos' => 941,
          ),
        ),
        'docComment' => '/**
 * Detailed debug information
 *
 * @deprecated Use \\Monolog\\Level::Debug
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 29,
      ),
      'INFO' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'INFO',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '200',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 109,
            'startFilePos' => 1110,
            'endTokenPos' => 109,
            'endFilePos' => 1112,
          ),
        ),
        'docComment' => '/**
 * Interesting events
 *
 * Examples: User logs in, SQL logs.
 *
 * @deprecated Use \\Monolog\\Level::Info
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 28,
      ),
      'NOTICE' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'NOTICE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '250',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 58,
            'startTokenPos' => 122,
            'startFilePos' => 1234,
            'endTokenPos' => 122,
            'endFilePos' => 1236,
          ),
        ),
        'docComment' => '/**
 * Uncommon events
 *
 * @deprecated Use \\Monolog\\Level::Notice
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 30,
      ),
      'WARNING' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'WARNING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '300',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 135,
            'startFilePos' => 1514,
            'endTokenPos' => 135,
            'endFilePos' => 1516,
          ),
        ),
        'docComment' => '/**
 * Exceptional occurrences that are not errors
 *
 * Examples: Use of deprecated APIs, poor use of an API,
 * undesirable things that are not necessarily wrong.
 *
 * @deprecated Use \\Monolog\\Level::Warning
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'ERROR' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'ERROR',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '400',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 148,
            'startFilePos' => 1635,
            'endTokenPos' => 148,
            'endFilePos' => 1637,
          ),
        ),
        'docComment' => '/**
 * Runtime errors
 *
 * @deprecated Use \\Monolog\\Level::Error
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 29,
      ),
      'CRITICAL' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'CRITICAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '500',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 161,
            'startFilePos' => 1847,
            'endTokenPos' => 161,
            'endFilePos' => 1849,
          ),
        ),
        'docComment' => '/**
 * Critical conditions
 *
 * Example: Application component unavailable, unexpected exception.
 *
 * @deprecated Use \\Monolog\\Level::Critical
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 32,
      ),
      'ALERT' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'ALERT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '550',
          'attributes' => 
          array (
            'startLine' => 94,
            'endLine' => 94,
            'startTokenPos' => 174,
            'startFilePos' => 2116,
            'endTokenPos' => 174,
            'endFilePos' => 2118,
          ),
        ),
        'docComment' => '/**
 * Action must be taken immediately
 *
 * Example: Entire website down, database unavailable, etc.
 * This should trigger the SMS alerts and wake you up.
 *
 * @deprecated Use \\Monolog\\Level::Alert
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 94,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 29,
      ),
      'EMERGENCY' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'EMERGENCY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '600',
          'attributes' => 
          array (
            'startLine' => 101,
            'endLine' => 101,
            'startTokenPos' => 187,
            'startFilePos' => 2244,
            'endTokenPos' => 187,
            'endFilePos' => 2246,
          ),
        ),
        'docComment' => '/**
 * Urgent alert.
 *
 * @deprecated Use \\Monolog\\Level::Emergency
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'API' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'API',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 109,
            'endLine' => 109,
            'startTokenPos' => 200,
            'startFilePos' => 2433,
            'endTokenPos' => 200,
            'endFilePos' => 2433,
          ),
        ),
        'docComment' => '/**
 * Monolog API version
 *
 * This is only bumped when API breaks are done and should
 * follow the major version of the library
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 109,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 25,
      ),
      'RFC_5424_LEVELS' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'RFC_5424_LEVELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[7 => \\Monolog\\Level::Debug, 6 => \\Monolog\\Level::Info, 5 => \\Monolog\\Level::Notice, 4 => \\Monolog\\Level::Warning, 3 => \\Monolog\\Level::Error, 2 => \\Monolog\\Level::Critical, 1 => \\Monolog\\Level::Alert, 0 => \\Monolog\\Level::Emergency]',
          'attributes' => 
          array (
            'startLine' => 116,
            'endLine' => 125,
            'startTokenPos' => 213,
            'startFilePos' => 2626,
            'endTokenPos' => 287,
            'endFilePos' => 2857,
          ),
        ),
        'docComment' => '/**
 * Mapping between levels numbers defined in RFC 5424 and Monolog ones
 *
 * @phpstan-var array<int, Level> $rfc_5424_levels
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 116,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'name' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'name',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 127,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'handlers' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'handlers',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * The handler stack
 *
 * @var list<HandlerInterface>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 134,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'processors' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'processors',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * Processors that will process all log records
 *
 * To process records of a single handler instead, add the processor on that specific handler
 *
 * @var array<(callable(LogRecord): LogRecord)|ProcessorInterface>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 143,
        'endLine' => 143,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'microsecondTimestamps' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'microsecondTimestamps',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'true',
          'attributes' => 
          array (
            'startLine' => 145,
            'endLine' => 145,
            'startTokenPos' => 323,
            'startFilePos' => 3334,
            'endTokenPos' => 323,
            'endFilePos' => 3337,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 145,
        'endLine' => 145,
        'startColumn' => 5,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timezone' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'timezone',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'DateTimeZone',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 147,
        'endLine' => 147,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'exceptionHandler' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'exceptionHandler',
        'modifiers' => 2,
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
                  'name' => 'Closure',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 149,
            'endLine' => 149,
            'startTokenPos' => 343,
            'startFilePos' => 3427,
            'endTokenPos' => 343,
            'endFilePos' => 3430,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 149,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 52,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logDepth' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'logDepth',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 154,
            'endLine' => 154,
            'startTokenPos' => 356,
            'startFilePos' => 3540,
            'endTokenPos' => 356,
            'endFilePos' => 3540,
          ),
        ),
        'docComment' => '/**
 * Keeps track of depth to prevent infinite logging loops
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 154,
        'endLine' => 154,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fiberLogDepth' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'fiberLogDepth',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'WeakMap',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * @var WeakMap<Fiber<mixed, mixed, mixed, mixed>, int> Keeps track of depth inside fibers to prevent infinite logging loops
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 159,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'detectCycles' => 
      array (
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'name' => 'detectCycles',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'true',
          'attributes' => 
          array (
            'startLine' => 165,
            'endLine' => 165,
            'startTokenPos' => 378,
            'startFilePos' => 3945,
            'endTokenPos' => 378,
            'endFilePos' => 3948,
          ),
        ),
        'docComment' => '/**
 * Whether to detect infinite logging loops
 * This can be disabled via {@see useLoggingLoopDetection} if you have async handlers that do not play well with this
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 165,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 38,
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
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'handlers' => 
          array (
            'name' => 'handlers',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 175,
                'endLine' => 175,
                'startTokenPos' => 400,
                'startFilePos' => 4581,
                'endTokenPos' => 401,
                'endFilePos' => 4582,
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 47,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'processors' => 
          array (
            'name' => 'processors',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 175,
                'endLine' => 175,
                'startTokenPos' => 410,
                'startFilePos' => 4605,
                'endTokenPos' => 411,
                'endFilePos' => 4606,
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 69,
            'endColumn' => 90,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'timezone' => 
          array (
            'name' => 'timezone',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 175,
                'endLine' => 175,
                'startTokenPos' => 422,
                'startFilePos' => 4639,
                'endTokenPos' => 422,
                'endFilePos' => 4642,
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
                      'name' => 'DateTimeZone',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 93,
            'endColumn' => 126,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string             $name       The logging channel, a simple descriptive name that is attached to all log records
 * @param list<HandlerInterface> $handlers   Optional stack of handlers, the first one in the array is called first, etc.
 * @param callable[]         $processors Optional array of processors
 * @param DateTimeZone|null  $timezone   Optional timezone, if not provided date_default_timezone_get() will be used
 *
 * @phpstan-param array<(callable(LogRecord): LogRecord)|ProcessorInterface> $processors
 */',
        'startLine' => 175,
        'endLine' => 182,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'getName' => 
      array (
        'name' => 'getName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 184,
        'endLine' => 187,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'withName' => 
      array (
        'name' => 'withName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 30,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return a new cloned instance with the name changed
 *
 * @return static
 */',
        'startLine' => 194,
        'endLine' => 200,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'pushHandler' => 
      array (
        'name' => 'pushHandler',
        'parameters' => 
        array (
          'handler' => 
          array (
            'name' => 'handler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Monolog\\Handler\\HandlerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 33,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Pushes a handler on to the stack.
 *
 * @return $this
 */',
        'startLine' => 207,
        'endLine' => 212,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'popHandler' => 
      array (
        'name' => 'popHandler',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Monolog\\Handler\\HandlerInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Pops a handler from the stack
 *
 * @throws \\LogicException If empty handler stack
 */',
        'startLine' => 219,
        'endLine' => 226,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'setHandlers' => 
      array (
        'name' => 'setHandlers',
        'parameters' => 
        array (
          'handlers' => 
          array (
            'name' => 'handlers',
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
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 33,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set handlers, replacing all existing ones.
 *
 * If a map is passed, keys will be ignored.
 *
 * @param  list<HandlerInterface> $handlers
 * @return $this
 */',
        'startLine' => 236,
        'endLine' => 244,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'getHandlers' => 
      array (
        'name' => 'getHandlers',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return list<HandlerInterface>
 */',
        'startLine' => 249,
        'endLine' => 252,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'pushProcessor' => 
      array (
        'name' => 'pushProcessor',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
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
                      'name' => 'Monolog\\Processor\\ProcessorInterface',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'callable',
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 35,
            'endColumn' => 71,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a processor on to the stack.
 *
 * @phpstan-param ProcessorInterface|(callable(LogRecord): LogRecord) $callback
 * @return $this
 */',
        'startLine' => 260,
        'endLine' => 265,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'popProcessor' => 
      array (
        'name' => 'popProcessor',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'callable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Removes the processor on top of the stack and returns it.
 *
 * @phpstan-return ProcessorInterface|(callable(LogRecord): LogRecord)
 * @throws \\LogicException If empty processor stack
 */',
        'startLine' => 273,
        'endLine' => 280,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'getProcessors' => 
      array (
        'name' => 'getProcessors',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return callable[]
 * @phpstan-return array<ProcessorInterface|(callable(LogRecord): LogRecord)>
 */',
        'startLine' => 286,
        'endLine' => 289,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'useMicrosecondTimestamps' => 
      array (
        'name' => 'useMicrosecondTimestamps',
        'parameters' => 
        array (
          'micro' => 
          array (
            'name' => 'micro',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 46,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Control the use of microsecond resolution timestamps in the \'datetime\'
 * member of new records.
 *
 * As of PHP7.1 microseconds are always included by the engine, so
 * there is no performance penalty and Monolog 2 enabled microseconds
 * by default. This function lets you disable them though in case you want
 * to suppress microseconds from the output.
 *
 * @param  bool  $micro True to use microtime() to create timestamps
 * @return $this
 */',
        'startLine' => 303,
        'endLine' => 308,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'useLoggingLoopDetection' => 
      array (
        'name' => 'useLoggingLoopDetection',
        'parameters' => 
        array (
          'detectCycles' => 
          array (
            'name' => 'detectCycles',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 313,
            'endLine' => 313,
            'startColumn' => 45,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return $this
 */',
        'startLine' => 313,
        'endLine' => 318,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'addRecord' => 
      array (
        'name' => 'addRecord',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
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
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Monolog\\Level',
                      'isIdentifier' => false,
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
            'startLine' => 332,
            'endLine' => 332,
            'startColumn' => 31,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 332,
            'endLine' => 332,
            'startColumn' => 49,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 332,
                'endLine' => 332,
                'startTokenPos' => 941,
                'startFilePos' => 8873,
                'endTokenPos' => 942,
                'endFilePos' => 8874,
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
            'startLine' => 332,
            'endLine' => 332,
            'startColumn' => 66,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'datetime' => 
          array (
            'name' => 'datetime',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 332,
                'endLine' => 332,
                'startTokenPos' => 953,
                'startFilePos' => 8928,
                'endTokenPos' => 953,
                'endFilePos' => 8931,
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
                      'name' => 'Monolog\\JsonSerializableDateTimeImmutable',
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
            'startLine' => 332,
            'endLine' => 332,
            'startColumn' => 87,
            'endColumn' => 141,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record.
 *
 * @param  int                    $level    The logging level (a Monolog or RFC 5424 level)
 * @param  string                 $message  The log message
 * @param  mixed[]                $context  The log context
 * @param  JsonSerializableDateTimeImmutable|null $datetime Optional log date to log into the past or future
 *
 * @return bool                   Whether the record has been processed
 *
 * @phpstan-param value-of<Level::VALUES>|Level $level
 */',
        'startLine' => 332,
        'endLine' => 411,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'close' => 
      array (
        'name' => 'close',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ends a log cycle and frees all resources used by handlers.
 *
 * Closing a Handler means flushing all buffers and freeing any open resources/handles.
 * Handlers that have been closed should be able to accept log records again and re-open
 * themselves on demand, but this may not always be possible depending on implementation.
 *
 * This is useful at the end of a request and will be called automatically on every handler
 * when they get destructed.
 */',
        'startLine' => 423,
        'endLine' => 428,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'reset' => 
      array (
        'name' => 'reset',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ends a log cycle and resets all handlers and processors to their initial state.
 *
 * Resetting a Handler or a Processor means flushing/cleaning all buffers, resetting internal
 * state, and getting it back to a state in which it can receive log records again.
 *
 * This is useful in case you want to avoid logs leaking between two requests or jobs when you
 * have a long running process like a worker or an application server serving multiple requests
 * in one process.
 */',
        'startLine' => 440,
        'endLine' => 453,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'getLevelName' => 
      array (
        'name' => 'getLevelName',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
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
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Monolog\\Level',
                      'isIdentifier' => false,
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
            'startLine' => 467,
            'endLine' => 467,
            'startColumn' => 41,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the name of the logging level as a string.
 *
 * This still returns a string instead of a Level for BC, but new code should not rely on this method.
 *
 * @throws \\Psr\\Log\\InvalidArgumentException If level is not defined
 *
 * @phpstan-param  value-of<Level::VALUES>|Level $level
 * @phpstan-return value-of<Level::NAMES>
 *
 * @deprecated Since 3.0, use {@see toMonologLevel} or {@see \\Monolog\\Level->getName()} instead
 */',
        'startLine' => 467,
        'endLine' => 470,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'toMonologLevel' => 
      array (
        'name' => 'toMonologLevel',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  2 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Monolog\\Level',
                      'isIdentifier' => false,
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
            'startLine' => 480,
            'endLine' => 480,
            'startColumn' => 43,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Monolog\\Level',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Converts PSR-3 levels to Monolog ones if necessary
 *
 * @param  int|string|Level|LogLevel::*      $level Level number (monolog) or name (PSR-3)
 * @throws \\Psr\\Log\\InvalidArgumentException If level is not defined
 *
 * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
 */',
        'startLine' => 480,
        'endLine' => 512,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'isHandling' => 
      array (
        'name' => 'isHandling',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
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
                      'name' => 'int',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  2 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Monolog\\Level',
                      'isIdentifier' => false,
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
            'startLine' => 519,
            'endLine' => 519,
            'startColumn' => 32,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Checks whether the Logger has a handler that listens on the given level
 *
 * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
 */',
        'startLine' => 519,
        'endLine' => 535,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'setExceptionHandler' => 
      array (
        'name' => 'setExceptionHandler',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
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
                      'name' => 'Closure',
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
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 41,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set a custom exception handler that will be called if adding a new record fails
 *
 * The Closure will receive an exception object and the record that failed to be logged
 *
 * @return $this
 */',
        'startLine' => 544,
        'endLine' => 549,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'getExceptionHandler' => 
      array (
        'name' => 'getExceptionHandler',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'Closure',
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 551,
        'endLine' => 554,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'log' => 
      array (
        'name' => 'log',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 25,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 33,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 567,
                'endLine' => 567,
                'startTokenPos' => 2170,
                'startFilePos' => 17422,
                'endTokenPos' => 2171,
                'endFilePos' => 17423,
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
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 62,
            'endColumn' => 80,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at an arbitrary level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param mixed             $level   The log level (a Monolog, PSR-3 or RFC 5424 level)
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 *
 * @phpstan-param Level|LogLevel::* $level
 */',
        'startLine' => 567,
        'endLine' => 582,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'debug' => 
      array (
        'name' => 'debug',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 592,
            'endLine' => 592,
            'startColumn' => 27,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 592,
                'endLine' => 592,
                'startTokenPos' => 2313,
                'startFilePos' => 18266,
                'endTokenPos' => 2314,
                'endFilePos' => 18267,
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
            'startLine' => 592,
            'endLine' => 592,
            'startColumn' => 56,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at the DEBUG level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 */',
        'startLine' => 592,
        'endLine' => 595,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'info' => 
      array (
        'name' => 'info',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 605,
            'endLine' => 605,
            'startColumn' => 26,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 605,
                'endLine' => 605,
                'startTokenPos' => 2363,
                'startFilePos' => 18685,
                'endTokenPos' => 2364,
                'endFilePos' => 18686,
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
            'startLine' => 605,
            'endLine' => 605,
            'startColumn' => 55,
            'endColumn' => 73,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at the INFO level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 */',
        'startLine' => 605,
        'endLine' => 608,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'notice' => 
      array (
        'name' => 'notice',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 618,
            'endLine' => 618,
            'startColumn' => 28,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 618,
                'endLine' => 618,
                'startTokenPos' => 2413,
                'startFilePos' => 19107,
                'endTokenPos' => 2414,
                'endFilePos' => 19108,
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
            'startLine' => 618,
            'endLine' => 618,
            'startColumn' => 57,
            'endColumn' => 75,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at the NOTICE level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 */',
        'startLine' => 618,
        'endLine' => 621,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'warning' => 
      array (
        'name' => 'warning',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 631,
            'endLine' => 631,
            'startColumn' => 29,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 631,
                'endLine' => 631,
                'startTokenPos' => 2463,
                'startFilePos' => 19533,
                'endTokenPos' => 2464,
                'endFilePos' => 19534,
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
            'startLine' => 631,
            'endLine' => 631,
            'startColumn' => 58,
            'endColumn' => 76,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at the WARNING level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 */',
        'startLine' => 631,
        'endLine' => 634,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'error' => 
      array (
        'name' => 'error',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 644,
            'endLine' => 644,
            'startColumn' => 27,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 644,
                'endLine' => 644,
                'startTokenPos' => 2513,
                'startFilePos' => 19956,
                'endTokenPos' => 2514,
                'endFilePos' => 19957,
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
            'startLine' => 644,
            'endLine' => 644,
            'startColumn' => 56,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at the ERROR level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 */',
        'startLine' => 644,
        'endLine' => 647,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'critical' => 
      array (
        'name' => 'critical',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 657,
            'endLine' => 657,
            'startColumn' => 30,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 657,
                'endLine' => 657,
                'startTokenPos' => 2563,
                'startFilePos' => 20383,
                'endTokenPos' => 2564,
                'endFilePos' => 20384,
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
            'startLine' => 657,
            'endLine' => 657,
            'startColumn' => 59,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at the CRITICAL level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 */',
        'startLine' => 657,
        'endLine' => 660,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'alert' => 
      array (
        'name' => 'alert',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 670,
            'endLine' => 670,
            'startColumn' => 27,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 670,
                'endLine' => 670,
                'startTokenPos' => 2613,
                'startFilePos' => 20807,
                'endTokenPos' => 2614,
                'endFilePos' => 20808,
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
            'startLine' => 670,
            'endLine' => 670,
            'startColumn' => 56,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at the ALERT level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 */',
        'startLine' => 670,
        'endLine' => 673,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'emergency' => 
      array (
        'name' => 'emergency',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Stringable',
                      'isIdentifier' => false,
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
            'startLine' => 683,
            'endLine' => 683,
            'startColumn' => 31,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 683,
                'endLine' => 683,
                'startTokenPos' => 2663,
                'startFilePos' => 21236,
                'endTokenPos' => 2664,
                'endFilePos' => 21237,
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
            'startLine' => 683,
            'endLine' => 683,
            'startColumn' => 60,
            'endColumn' => 78,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a log record at the EMERGENCY level.
 *
 * This method allows for compatibility with common interfaces.
 *
 * @param string|Stringable $message The log message
 * @param mixed[]           $context The log context
 */',
        'startLine' => 683,
        'endLine' => 686,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'setTimezone' => 
      array (
        'name' => 'setTimezone',
        'parameters' => 
        array (
          'tz' => 
          array (
            'name' => 'tz',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'DateTimeZone',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 693,
            'endLine' => 693,
            'startColumn' => 33,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the timezone to be used for the timestamp of log records.
 *
 * @return $this
 */',
        'startLine' => 693,
        'endLine' => 698,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'getTimezone' => 
      array (
        'name' => 'getTimezone',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'DateTimeZone',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the timezone to be used for the timestamp of log records.
 */',
        'startLine' => 703,
        'endLine' => 706,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      'handleException' => 
      array (
        'name' => 'handleException',
        'parameters' => 
        array (
          'e' => 
          array (
            'name' => 'e',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 712,
            'endLine' => 712,
            'startColumn' => 40,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'record' => 
          array (
            'name' => 'record',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Monolog\\LogRecord',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 712,
            'endLine' => 712,
            'startColumn' => 54,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Delegates exception management to the custom exception handler,
 * or throws the exception if no custom handler is set.
 */',
        'startLine' => 712,
        'endLine' => 719,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      '__serialize' => 
      array (
        'name' => '__serialize',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, mixed>
 */',
        'startLine' => 724,
        'endLine' => 736,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
        'aliasName' => NULL,
      ),
      '__unserialize' => 
      array (
        'name' => '__unserialize',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 741,
            'endLine' => 741,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param array<string, mixed> $data
 */',
        'startLine' => 741,
        'endLine' => 750,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Monolog',
        'declaringClassName' => 'Monolog\\Logger',
        'implementingClassName' => 'Monolog\\Logger',
        'currentClassName' => 'Monolog\\Logger',
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