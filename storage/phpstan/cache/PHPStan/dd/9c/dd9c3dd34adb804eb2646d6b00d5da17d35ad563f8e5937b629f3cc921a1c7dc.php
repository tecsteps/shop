<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Collections/Traits/TransformsToResourceCollection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Support\Traits\TransformsToResourceCollection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-226b1867a790e6da048a1b9a717b8af16ca169b1eac84605d5ae76a2aaf96f40-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Collections/Traits/TransformsToResourceCollection.php',
      ),
    ),
    'namespace' => 'Illuminate\\Support\\Traits',
    'name' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
    'shortName' => 'TransformsToResourceCollection',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 121,
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
    ),
    'immediateMethods' => 
    array (
      'toResourceCollection' => 
      array (
        'name' => 'toResourceCollection',
        'parameters' => 
        array (
          'resourceClass' => 
          array (
            'name' => 'resourceClass',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 22,
                'endLine' => 22,
                'startTokenPos' => 58,
                'startFilePos' => 701,
                'endTokenPos' => 58,
                'endFilePos' => 704,
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
                      'name' => 'string',
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 42,
            'endColumn' => 70,
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
            'name' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new resource collection instance for the given resource.
 *
 * @param  class-string<\\Illuminate\\Http\\Resources\\Json\\JsonResource>|null  $resourceClass
 * @return \\Illuminate\\Http\\Resources\\Json\\ResourceCollection
 *
 * @throws \\Throwable
 */',
        'startLine' => 22,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support\\Traits',
        'declaringClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'implementingClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'currentClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'aliasName' => NULL,
      ),
      'guessResourceCollection' => 
      array (
        'name' => 'guessResourceCollection',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Guess the resource collection for the items.
 *
 * @return \\Illuminate\\Http\\Resources\\Json\\ResourceCollection
 *
 * @throws \\Throwable
 */',
        'startLine' => 38,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Support\\Traits',
        'declaringClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'implementingClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'currentClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'aliasName' => NULL,
      ),
      'resolveResourceFromAttribute' => 
      array (
        'name' => 'resolveResourceFromAttribute',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
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
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 53,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
                  'name' => 'string',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the resource class from the class attribute.
 *
 * @param  class-string<\\Illuminate\\Http\\Resources\\Json\\JsonResource>  $class
 * @return class-string<*>|null
 */',
        'startLine' => 90,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Support\\Traits',
        'declaringClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'implementingClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'currentClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'aliasName' => NULL,
      ),
      'resolveResourceCollectionFromAttribute' => 
      array (
        'name' => 'resolveResourceCollectionFromAttribute',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 63,
            'endColumn' => 75,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
                  'name' => 'string',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the resource collection class from the class attribute.
 *
 * @param  class-string<\\Illuminate\\Http\\Resources\\Json\\ResourceCollection>  $class
 * @return class-string<*>|null
 */',
        'startLine' => 109,
        'endLine' => 120,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Support\\Traits',
        'declaringClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'implementingClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
        'currentClassName' => 'Illuminate\\Support\\Traits\\TransformsToResourceCollection',
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