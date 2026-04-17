<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Contracts/Database/Eloquent/SupportsPartialRelations.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Contracts\Database\Eloquent\SupportsPartialRelations
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-2f8d99e456bb8f9022e221bbbf2c63b80366b155fb565ebf9e228a9062f7ccb9-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Contracts/Database/Eloquent/SupportsPartialRelations.php',
      ),
    ),
    'namespace' => 'Illuminate\\Contracts\\Database\\Eloquent',
    'name' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
    'shortName' => 'SupportsPartialRelations',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 5,
    'endLine' => 30,
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
      'ofMany' => 
      array (
        'name' => 'ofMany',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'id\'',
              'attributes' => 
              array (
                'startLine' => 15,
                'endLine' => 15,
                'startTokenPos' => 25,
                'startFilePos' => 389,
                'endTokenPos' => 25,
                'endFilePos' => 392,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 28,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'aggregate' => 
          array (
            'name' => 'aggregate',
            'default' => 
            array (
              'code' => '\'MAX\'',
              'attributes' => 
              array (
                'startLine' => 15,
                'endLine' => 15,
                'startTokenPos' => 32,
                'startFilePos' => 408,
                'endTokenPos' => 32,
                'endFilePos' => 412,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 44,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'relation' => 
          array (
            'name' => 'relation',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 15,
                'endLine' => 15,
                'startTokenPos' => 39,
                'startFilePos' => 427,
                'endTokenPos' => 39,
                'endFilePos' => 430,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 64,
            'endColumn' => 79,
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
 * Indicate that the relation is a single result of a larger one-to-many relationship.
 *
 * @param  string|null  $column
 * @param  string|\\Closure|null  $aggregate
 * @param  string|null  $relation
 * @return $this
 */',
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 81,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Database\\Eloquent',
        'declaringClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'implementingClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'currentClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'aliasName' => NULL,
      ),
      'isOneOfMany' => 
      array (
        'name' => 'isOneOfMany',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine whether the relationship is a one-of-many relationship.
 *
 * @return bool
 */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Database\\Eloquent',
        'declaringClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'implementingClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'currentClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'aliasName' => NULL,
      ),
      'getOneOfManySubQuery' => 
      array (
        'name' => 'getOneOfManySubQuery',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the one of many inner join subselect query builder instance.
 *
 * @return \\Illuminate\\Database\\Eloquent\\Builder|void
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Database\\Eloquent',
        'declaringClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'implementingClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
        'currentClassName' => 'Illuminate\\Contracts\\Database\\Eloquent\\SupportsPartialRelations',
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