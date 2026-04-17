<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Http/Resources/Json/AnonymousResourceCollection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Http\Resources\Json\AnonymousResourceCollection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-7e2cd74afa33acc47acc36ddf5b61c9b689e41f995218fa8163c744214ee1f00-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Http/Resources/Json/AnonymousResourceCollection.php',
      ),
    ),
    'namespace' => 'Illuminate\\Http\\Resources\\Json',
    'name' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
    'shortName' => 'AnonymousResourceCollection',
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
    'endLine' => 43,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
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
      'collects' => 
      array (
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'name' => 'collects',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The name of the resource being collected.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'preserveKeys' => 
      array (
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'name' => 'preserveKeys',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 32,
            'startFilePos' => 355,
            'endTokenPos' => 32,
            'endFilePos' => 359,
          ),
        ),
        'docComment' => '/**
 * Indicates if the collection keys should be preserved.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 33,
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
          'resource' => 
          array (
            'name' => 'resource',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 33,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'collects' => 
          array (
            'name' => 'collects',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 44,
            'endColumn' => 52,
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
 * Create a new anonymous resource collection.
 *
 * @param  mixed  $resource
 * @param  string  $collects
 */',
        'startLine' => 27,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'aliasName' => NULL,
      ),
      'preserveKeys' => 
      array (
        'name' => 'preserveKeys',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 37,
                'endLine' => 37,
                'startTokenPos' => 84,
                'startFilePos' => 772,
                'endTokenPos' => 84,
                'endFilePos' => 775,
              ),
            ),
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
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 34,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'static',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the collection keys should be preserved.
 */',
        'startLine' => 37,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection',
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