<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Cache/RateLimiting/Limit.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Cache\RateLimiting\Limit
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-429213e04f75318b5fd8df901f95100a726f524bd77888f2b0852641e41457d3-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Cache/RateLimiting/Limit.php',
      ),
    ),
    'namespace' => 'Illuminate\\Cache\\RateLimiting',
    'name' => 'Illuminate\\Cache\\RateLimiting\\Limit',
    'shortName' => 'Limit',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 5,
    'endLine' => 176,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'key' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'name' => 'key',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The rate limit signature key.
 *
 * @var mixed
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 5,
        'endColumn' => 16,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'maxAttempts' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'name' => 'maxAttempts',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The maximum number of attempts allowed within the given number of seconds.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'decaySeconds' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'name' => 'decaySeconds',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The number of seconds until the rate limit is reset.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'afterCallback' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'name' => 'afterCallback',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 42,
            'startFilePos' => 579,
            'endTokenPos' => 42,
            'endFilePos' => 582,
          ),
        ),
        'docComment' => '/**
 * The after callback used to determine if the limiter should be hit.
 *
 * @var ?callable
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
      'responseCallback' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'name' => 'responseCallback',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The response generator callback.
 *
 * @var callable
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 29,
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
          'key' => 
          array (
            'name' => 'key',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 49,
                'endLine' => 49,
                'startTokenPos' => 64,
                'startFilePos' => 893,
                'endTokenPos' => 64,
                'endFilePos' => 894,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 33,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'maxAttempts' => 
          array (
            'name' => 'maxAttempts',
            'default' => 
            array (
              'code' => '60',
              'attributes' => 
              array (
                'startLine' => 49,
                'endLine' => 49,
                'startTokenPos' => 73,
                'startFilePos' => 916,
                'endTokenPos' => 73,
                'endFilePos' => 917,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 44,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'decaySeconds' => 
          array (
            'name' => 'decaySeconds',
            'default' => 
            array (
              'code' => '60',
              'attributes' => 
              array (
                'startLine' => 49,
                'endLine' => 49,
                'startTokenPos' => 82,
                'startFilePos' => 940,
                'endTokenPos' => 82,
                'endFilePos' => 941,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 67,
            'endColumn' => 88,
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
 * Create a new limit instance.
 *
 * @param  mixed  $key
 * @param  int  $maxAttempts
 * @param  int  $decaySeconds
 */',
        'startLine' => 49,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'perSecond' => 
      array (
        'name' => 'perSecond',
        'parameters' => 
        array (
          'maxAttempts' => 
          array (
            'name' => 'maxAttempts',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'decaySeconds' => 
          array (
            'name' => 'decaySeconds',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 63,
                'endLine' => 63,
                'startTokenPos' => 133,
                'startFilePos' => 1283,
                'endTokenPos' => 133,
                'endFilePos' => 1283,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
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
 * Create a new rate limit.
 *
 * @param  int  $maxAttempts
 * @param  int  $decaySeconds
 * @return static
 */',
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'perMinute' => 
      array (
        'name' => 'perMinute',
        'parameters' => 
        array (
          'maxAttempts' => 
          array (
            'name' => 'maxAttempts',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'decayMinutes' => 
          array (
            'name' => 'decayMinutes',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 75,
                'endLine' => 75,
                'startTokenPos' => 173,
                'startFilePos' => 1570,
                'endTokenPos' => 173,
                'endFilePos' => 1570,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
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
 * Create a new rate limit.
 *
 * @param  int  $maxAttempts
 * @param  int  $decayMinutes
 * @return static
 */',
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'perMinutes' => 
      array (
        'name' => 'perMinutes',
        'parameters' => 
        array (
          'decayMinutes' => 
          array (
            'name' => 'decayMinutes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 39,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'maxAttempts' => 
          array (
            'name' => 'maxAttempts',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 54,
            'endColumn' => 65,
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
 * Create a new rate limit using minutes as decay time.
 *
 * @param  int  $decayMinutes
 * @param  int  $maxAttempts
 * @return static
 */',
        'startLine' => 87,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'perHour' => 
      array (
        'name' => 'perHour',
        'parameters' => 
        array (
          'maxAttempts' => 
          array (
            'name' => 'maxAttempts',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 36,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'decayHours' => 
          array (
            'name' => 'decayHours',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 99,
                'endLine' => 99,
                'startTokenPos' => 257,
                'startFilePos' => 2199,
                'endTokenPos' => 257,
                'endFilePos' => 2199,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 50,
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
 * Create a new rate limit using hours as decay time.
 *
 * @param  int  $maxAttempts
 * @param  int  $decayHours
 * @return static
 */',
        'startLine' => 99,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'perDay' => 
      array (
        'name' => 'perDay',
        'parameters' => 
        array (
          'maxAttempts' => 
          array (
            'name' => 'maxAttempts',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'decayDays' => 
          array (
            'name' => 'decayDays',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 111,
                'endLine' => 111,
                'startTokenPos' => 305,
                'startFilePos' => 2510,
                'endTokenPos' => 305,
                'endFilePos' => 2510,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 49,
            'endColumn' => 62,
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
 * Create a new rate limit using days as decay time.
 *
 * @param  int  $maxAttempts
 * @param  int  $decayDays
 * @return static
 */',
        'startLine' => 111,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'none' => 
      array (
        'name' => 'none',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new unlimited rate limit.
 *
 * @return static
 */',
        'startLine' => 121,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'by' => 
      array (
        'name' => 'by',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 24,
            'endColumn' => 27,
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
 * Set the key of the rate limit.
 *
 * @param  mixed  $key
 * @return $this
 */',
        'startLine' => 132,
        'endLine' => 137,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'after' => 
      array (
        'name' => 'after',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 27,
            'endColumn' => 35,
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
 * Set the callback to determine if the limiter should be hit.
 *
 * @param  callable  $callback
 * @return $this
 */',
        'startLine' => 145,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'response' => 
      array (
        'name' => 'response',
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 30,
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
 * Set the callback that should generate the response when the limit is exceeded.
 *
 * @param  callable  $callback
 * @return $this
 */',
        'startLine' => 158,
        'endLine' => 163,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'aliasName' => NULL,
      ),
      'fallbackKey' => 
      array (
        'name' => 'fallbackKey',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get a potential fallback key for the limit.
 *
 * @return string
 */',
        'startLine' => 170,
        'endLine' => 175,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\RateLimiting',
        'declaringClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'implementingClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
        'currentClassName' => 'Illuminate\\Cache\\RateLimiting\\Limit',
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