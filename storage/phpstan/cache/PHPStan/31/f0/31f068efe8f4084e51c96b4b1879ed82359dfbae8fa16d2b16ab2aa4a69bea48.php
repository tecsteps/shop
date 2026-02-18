<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Eloquent/Relations/HasOneOrMany.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Eloquent\Relations\HasOneOrMany
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-15a381547e09cc0baea235e7a9aeafbfa78795d6affc10a36baaf25969e0b8d2-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Eloquent/Relations/HasOneOrMany.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
    'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
    'shortName' => 'HasOneOrMany',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * @template TRelatedModel of \\Illuminate\\Database\\Eloquent\\Model
 * @template TDeclaringModel of \\Illuminate\\Database\\Eloquent\\Model
 * @template TResult
 *
 * @extends \\Illuminate\\Database\\Eloquent\\Relations\\Relation<TRelatedModel, TDeclaringModel, TResult>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 623,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Relation',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithDictionary',
      1 => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\SupportsInverseRelations',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'foreignKey' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'name' => 'foreignKey',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The foreign key of the parent model.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 26,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'localKey' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'name' => 'localKey',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The local key of the parent model.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 24,
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
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Builder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parent' => 
          array (
            'name' => 'parent',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Model',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'foreignKey' => 
          array (
            'name' => 'foreignKey',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 64,
            'endColumn' => 74,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'localKey' => 
          array (
            'name' => 'localKey',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 77,
            'endColumn' => 85,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new has one or many relationship instance.
 *
 * @param  \\Illuminate\\Database\\Eloquent\\Builder<TRelatedModel>  $query
 * @param  TDeclaringModel  $parent
 * @param  string  $foreignKey
 * @param  string  $localKey
 */',
        'startLine' => 47,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'make' => 
      array (
        'name' => 'make',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 61,
                'endLine' => 61,
                'startTokenPos' => 158,
                'startFilePos' => 1753,
                'endTokenPos' => 159,
                'endFilePos' => 1754,
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
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 26,
            'endColumn' => 47,
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
 * Create and return an un-saved instance of the related model.
 *
 * @param  array  $attributes
 * @return TRelatedModel
 */',
        'startLine' => 61,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'makeMany' => 
      array (
        'name' => 'makeMany',
        'parameters' => 
        array (
          'records' => 
          array (
            'name' => 'records',
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
            'startColumn' => 30,
            'endColumn' => 37,
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
 * Create and return an un-saved instance of the related models.
 *
 * @param  iterable  $records
 * @return \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>
 */',
        'startLine' => 75,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'addConstraints' => 
      array (
        'name' => 'addConstraints',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the base constraints on the relation query.
 *
 * @return void
 */',
        'startLine' => 91,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'addEagerConstraints' => 
      array (
        'name' => 'addEagerConstraints',
        'parameters' => 
        array (
          'models' => 
          array (
            'name' => 'models',
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
            'startLine' => 103,
            'endLine' => 103,
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
        'docComment' => '/** @inheritDoc */',
        'startLine' => 103,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'matchOne' => 
      array (
        'name' => 'matchOne',
        'parameters' => 
        array (
          'models' => 
          array (
            'name' => 'models',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 30,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 45,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'relation' => 
          array (
            'name' => 'relation',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 74,
            'endColumn' => 82,
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
 * Match the eagerly loaded results to their single parents.
 *
 * @param  array<int, TDeclaringModel>  $models
 * @param  \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>  $results
 * @param  string  $relation
 * @return array<int, TDeclaringModel>
 */',
        'startLine' => 123,
        'endLine' => 126,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'matchMany' => 
      array (
        'name' => 'matchMany',
        'parameters' => 
        array (
          'models' => 
          array (
            'name' => 'models',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 46,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'relation' => 
          array (
            'name' => 'relation',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 75,
            'endColumn' => 83,
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
 * Match the eagerly loaded results to their many parents.
 *
 * @param  array<int, TDeclaringModel>  $models
 * @param  \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>  $results
 * @param  string  $relation
 * @return array<int, TDeclaringModel>
 */',
        'startLine' => 136,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'matchOneOrMany' => 
      array (
        'name' => 'matchOneOrMany',
        'parameters' => 
        array (
          'models' => 
          array (
            'name' => 'models',
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 39,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 54,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'relation' => 
          array (
            'name' => 'relation',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 83,
            'endColumn' => 91,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 94,
            'endColumn' => 98,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Match the eagerly loaded results to their many parents.
 *
 * @param  array<int, TDeclaringModel>  $models
 * @param  \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>  $results
 * @param  string  $relation
 * @param  string  $type
 * @return array<int, TDeclaringModel>
 */',
        'startLine' => 150,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getRelationValue' => 
      array (
        'name' => 'getRelationValue',
        'parameters' => 
        array (
          'dictionary' => 
          array (
            'name' => 'dictionary',
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 41,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 60,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 66,
            'endColumn' => 70,
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
 * Get the value of a relationship by one or many type.
 *
 * @param  array  $dictionary
 * @param  string  $key
 * @param  string  $type
 * @return mixed
 */',
        'startLine' => 183,
        'endLine' => 188,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'buildDictionary' => 
      array (
        'name' => 'buildDictionary',
        'parameters' => 
        array (
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 40,
            'endColumn' => 66,
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
 * Build model dictionary keyed by the relation\'s foreign key.
 *
 * @param  \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>  $results
 * @return array<array<array-key, TRelatedModel>>
 */',
        'startLine' => 196,
        'endLine' => 215,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'findOrNew' => 
      array (
        'name' => 'findOrNew',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 224,
            'endLine' => 224,
            'startColumn' => 31,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'columns' => 
          array (
            'name' => 'columns',
            'default' => 
            array (
              'code' => '[\'*\']',
              'attributes' => 
              array (
                'startLine' => 224,
                'endLine' => 224,
                'startTokenPos' => 871,
                'startFilePos' => 7025,
                'endTokenPos' => 873,
                'endFilePos' => 7029,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 224,
            'endLine' => 224,
            'startColumn' => 36,
            'endColumn' => 51,
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
 * Find a model by its primary key or return a new instance of the related model.
 *
 * @param  mixed  $id
 * @param  array  $columns
 * @return ($id is (\\Illuminate\\Contracts\\Support\\Arrayable<array-key, mixed>|array<mixed>) ? \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel> : TRelatedModel)
 */',
        'startLine' => 224,
        'endLine' => 233,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'firstOrNew' => 
      array (
        'name' => 'firstOrNew',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 242,
                'endLine' => 242,
                'startTokenPos' => 945,
                'startFilePos' => 7514,
                'endTokenPos' => 946,
                'endFilePos' => 7515,
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 32,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'values' => 
          array (
            'name' => 'values',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 242,
                'endLine' => 242,
                'startTokenPos' => 955,
                'startFilePos' => 7534,
                'endTokenPos' => 956,
                'endFilePos' => 7535,
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 56,
            'endColumn' => 73,
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
 * Get the first related model record matching the attributes or instantiate it.
 *
 * @param  array  $attributes
 * @param  array  $values
 * @return TRelatedModel
 */',
        'startLine' => 242,
        'endLine' => 251,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'firstOrCreate' => 
      array (
        'name' => 'firstOrCreate',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 260,
                'endLine' => 260,
                'startTokenPos' => 1036,
                'startFilePos' => 8091,
                'endTokenPos' => 1037,
                'endFilePos' => 8092,
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 35,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'values' => 
          array (
            'name' => 'values',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 260,
                'endLine' => 260,
                'startTokenPos' => 1048,
                'startFilePos' => 8119,
                'endTokenPos' => 1049,
                'endFilePos' => 8120,
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
                      'name' => 'Closure',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'array',
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
            'startColumn' => 59,
            'endColumn' => 84,
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
 * Get the first record matching the attributes. If the record is not found, create it.
 *
 * @param  array  $attributes
 * @param  (\\Closure(): array)|array  $values
 * @return TRelatedModel
 */',
        'startLine' => 260,
        'endLine' => 267,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'createOrFirst' => 
      array (
        'name' => 'createOrFirst',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 276,
                'endLine' => 276,
                'startTokenPos' => 1120,
                'startFilePos' => 8625,
                'endTokenPos' => 1121,
                'endFilePos' => 8626,
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
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 35,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'values' => 
          array (
            'name' => 'values',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 276,
                'endLine' => 276,
                'startTokenPos' => 1132,
                'startFilePos' => 8653,
                'endTokenPos' => 1133,
                'endFilePos' => 8654,
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
                      'name' => 'Closure',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'array',
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
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 59,
            'endColumn' => 84,
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
 * Attempt to create the record. If a unique constraint violation occurs, attempt to find the matching record.
 *
 * @param  array  $attributes
 * @param  (\\Closure(): array)|array  $values
 * @return TRelatedModel
 */',
        'startLine' => 276,
        'endLine' => 283,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'updateOrCreate' => 
      array (
        'name' => 'updateOrCreate',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
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
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 36,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'values' => 
          array (
            'name' => 'values',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 292,
                'endLine' => 292,
                'startTokenPos' => 1236,
                'startFilePos' => 9238,
                'endTokenPos' => 1237,
                'endFilePos' => 9239,
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
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 55,
            'endColumn' => 72,
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
 * Create or update a related record matching the attributes, and fill it with values.
 *
 * @param  array  $attributes
 * @param  array  $values
 * @return TRelatedModel
 */',
        'startLine' => 292,
        'endLine' => 299,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'upsert' => 
      array (
        'name' => 'upsert',
        'parameters' => 
        array (
          'values' => 
          array (
            'name' => 'values',
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
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 28,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'uniqueBy' => 
          array (
            'name' => 'uniqueBy',
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
            'startColumn' => 43,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'update' => 
          array (
            'name' => 'update',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 309,
                'endLine' => 309,
                'startTokenPos' => 1323,
                'startFilePos' => 9746,
                'endTokenPos' => 1323,
                'endFilePos' => 9749,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 54,
            'endColumn' => 67,
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
 * Insert new records or update the existing ones.
 *
 * @param  array  $values
 * @param  array|string  $uniqueBy
 * @param  array|null  $update
 * @return int
 */',
        'startLine' => 309,
        'endLine' => 320,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
        'parameters' => 
        array (
          'model' => 
          array (
            'name' => 'model',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Model',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 328,
            'endLine' => 328,
            'startColumn' => 26,
            'endColumn' => 37,
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
 * Attach a model instance to the parent model.
 *
 * @param  TRelatedModel  $model
 * @return TRelatedModel|false
 */',
        'startLine' => 328,
        'endLine' => 333,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'saveQuietly' => 
      array (
        'name' => 'saveQuietly',
        'parameters' => 
        array (
          'model' => 
          array (
            'name' => 'model',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Model',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 33,
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
 * Attach a model instance without raising any events to the parent model.
 *
 * @param  TRelatedModel  $model
 * @return TRelatedModel|false
 */',
        'startLine' => 341,
        'endLine' => 346,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'saveMany' => 
      array (
        'name' => 'saveMany',
        'parameters' => 
        array (
          'models' => 
          array (
            'name' => 'models',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 354,
            'endLine' => 354,
            'startColumn' => 30,
            'endColumn' => 36,
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
 * Attach a collection of models to the parent instance.
 *
 * @param  iterable<TRelatedModel>  $models
 * @return iterable<TRelatedModel>
 */',
        'startLine' => 354,
        'endLine' => 361,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'saveManyQuietly' => 
      array (
        'name' => 'saveManyQuietly',
        'parameters' => 
        array (
          'models' => 
          array (
            'name' => 'models',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 369,
            'endLine' => 369,
            'startColumn' => 37,
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
 * Attach a collection of models to the parent instance without raising any events to the parent model.
 *
 * @param  iterable<TRelatedModel>  $models
 * @return iterable<TRelatedModel>
 */',
        'startLine' => 369,
        'endLine' => 374,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 382,
                'endLine' => 382,
                'startTokenPos' => 1621,
                'startFilePos' => 11643,
                'endTokenPos' => 1622,
                'endFilePos' => 11644,
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
            'startLine' => 382,
            'endLine' => 382,
            'startColumn' => 28,
            'endColumn' => 49,
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
 * Create a new instance of the related model.
 *
 * @param  array  $attributes
 * @return TRelatedModel
 */',
        'startLine' => 382,
        'endLine' => 391,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'createQuietly' => 
      array (
        'name' => 'createQuietly',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 399,
                'endLine' => 399,
                'startTokenPos' => 1692,
                'startFilePos' => 12147,
                'endTokenPos' => 1693,
                'endFilePos' => 12148,
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
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 35,
            'endColumn' => 56,
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
 * Create a new instance of the related model without raising any events to the parent model.
 *
 * @param  array  $attributes
 * @return TRelatedModel
 */',
        'startLine' => 399,
        'endLine' => 402,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'forceCreate' => 
      array (
        'name' => 'forceCreate',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 410,
                'endLine' => 410,
                'startTokenPos' => 1736,
                'startFilePos' => 12450,
                'endTokenPos' => 1737,
                'endFilePos' => 12451,
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
            'startLine' => 410,
            'endLine' => 410,
            'startColumn' => 33,
            'endColumn' => 54,
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
 * Create a new instance of the related model. Allow mass-assignment.
 *
 * @param  array  $attributes
 * @return TRelatedModel
 */',
        'startLine' => 410,
        'endLine' => 415,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'forceCreateQuietly' => 
      array (
        'name' => 'forceCreateQuietly',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 423,
                'endLine' => 423,
                'startTokenPos' => 1793,
                'startFilePos' => 12880,
                'endTokenPos' => 1794,
                'endFilePos' => 12881,
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
            'startLine' => 423,
            'endLine' => 423,
            'startColumn' => 40,
            'endColumn' => 61,
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
 * Create a new instance of the related model with mass assignment without raising model events.
 *
 * @param  array  $attributes
 * @return TRelatedModel
 */',
        'startLine' => 423,
        'endLine' => 426,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'createMany' => 
      array (
        'name' => 'createMany',
        'parameters' => 
        array (
          'records' => 
          array (
            'name' => 'records',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 434,
            'endLine' => 434,
            'startColumn' => 32,
            'endColumn' => 48,
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
 * Create a Collection of new instances of the related model.
 *
 * @param  iterable  $records
 * @return \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>
 */',
        'startLine' => 434,
        'endLine' => 443,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'createManyQuietly' => 
      array (
        'name' => 'createManyQuietly',
        'parameters' => 
        array (
          'records' => 
          array (
            'name' => 'records',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 451,
            'endLine' => 451,
            'startColumn' => 39,
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
 * Create a Collection of new instances of the related model without raising any events to the parent model.
 *
 * @param  iterable  $records
 * @return \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>
 */',
        'startLine' => 451,
        'endLine' => 454,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'forceCreateMany' => 
      array (
        'name' => 'forceCreateMany',
        'parameters' => 
        array (
          'records' => 
          array (
            'name' => 'records',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 462,
            'endLine' => 462,
            'startColumn' => 37,
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
 * Create a Collection of new instances of the related model, allowing mass-assignment.
 *
 * @param  iterable  $records
 * @return \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>
 */',
        'startLine' => 462,
        'endLine' => 471,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'forceCreateManyQuietly' => 
      array (
        'name' => 'forceCreateManyQuietly',
        'parameters' => 
        array (
          'records' => 
          array (
            'name' => 'records',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 479,
            'endLine' => 479,
            'startColumn' => 44,
            'endColumn' => 60,
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
 * Create a Collection of new instances of the related model, allowing mass-assignment and without raising any events to the parent model.
 *
 * @param  iterable  $records
 * @return \\Illuminate\\Database\\Eloquent\\Collection<int, TRelatedModel>
 */',
        'startLine' => 479,
        'endLine' => 482,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'setForeignAttributesForCreate' => 
      array (
        'name' => 'setForeignAttributesForCreate',
        'parameters' => 
        array (
          'model' => 
          array (
            'name' => 'model',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Model',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 490,
            'endLine' => 490,
            'startColumn' => 54,
            'endColumn' => 65,
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
 * Set the foreign ID for creating a related model.
 *
 * @param  TRelatedModel  $model
 * @return void
 */',
        'startLine' => 490,
        'endLine' => 503,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getRelationExistenceQuery' => 
      array (
        'name' => 'getRelationExistenceQuery',
        'parameters' => 
        array (
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Builder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 506,
            'endLine' => 506,
            'startColumn' => 47,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parentQuery' => 
          array (
            'name' => 'parentQuery',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Builder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 506,
            'endLine' => 506,
            'startColumn' => 63,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'columns' => 
          array (
            'name' => 'columns',
            'default' => 
            array (
              'code' => '[\'*\']',
              'attributes' => 
              array (
                'startLine' => 506,
                'endLine' => 506,
                'startTokenPos' => 2155,
                'startFilePos' => 15463,
                'endTokenPos' => 2157,
                'endFilePos' => 15467,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 506,
            'endLine' => 506,
            'startColumn' => 85,
            'endColumn' => 100,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @inheritDoc */',
        'startLine' => 506,
        'endLine' => 513,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getRelationExistenceQueryForSelfRelation' => 
      array (
        'name' => 'getRelationExistenceQueryForSelfRelation',
        'parameters' => 
        array (
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Builder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 523,
            'endLine' => 523,
            'startColumn' => 62,
            'endColumn' => 75,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parentQuery' => 
          array (
            'name' => 'parentQuery',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Eloquent\\Builder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 523,
            'endLine' => 523,
            'startColumn' => 78,
            'endColumn' => 97,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'columns' => 
          array (
            'name' => 'columns',
            'default' => 
            array (
              'code' => '[\'*\']',
              'attributes' => 
              array (
                'startLine' => 523,
                'endLine' => 523,
                'startTokenPos' => 2244,
                'startFilePos' => 16213,
                'endTokenPos' => 2246,
                'endFilePos' => 16217,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 523,
            'endLine' => 523,
            'startColumn' => 100,
            'endColumn' => 115,
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
 * Add the constraints for a relationship query on the same table.
 *
 * @param  \\Illuminate\\Database\\Eloquent\\Builder<TRelatedModel>  $query
 * @param  \\Illuminate\\Database\\Eloquent\\Builder<TDeclaringModel>  $parentQuery
 * @param  mixed  $columns
 * @return \\Illuminate\\Database\\Eloquent\\Builder<TRelatedModel>
 */',
        'startLine' => 523,
        'endLine' => 532,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'take' => 
      array (
        'name' => 'take',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 26,
            'endColumn' => 31,
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
 * Alias to set the "limit" value of the query.
 *
 * @param  int  $value
 * @return $this
 */',
        'startLine' => 540,
        'endLine' => 543,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'limit' => 
      array (
        'name' => 'limit',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 551,
            'endLine' => 551,
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
 * Set the "limit" value of the query.
 *
 * @param  int  $value
 * @return $this
 */',
        'startLine' => 551,
        'endLine' => 560,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getExistenceCompareKey' => 
      array (
        'name' => 'getExistenceCompareKey',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the key for comparing against the parent key in "has" query.
 *
 * @return string
 */',
        'startLine' => 567,
        'endLine' => 570,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getParentKey' => 
      array (
        'name' => 'getParentKey',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the key value of the parent\'s local key.
 *
 * @return mixed
 */',
        'startLine' => 577,
        'endLine' => 580,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getQualifiedParentKeyName' => 
      array (
        'name' => 'getQualifiedParentKeyName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the fully qualified parent key name.
 *
 * @return string
 */',
        'startLine' => 587,
        'endLine' => 590,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getForeignKeyName' => 
      array (
        'name' => 'getForeignKeyName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the plain foreign key.
 *
 * @return string
 */',
        'startLine' => 597,
        'endLine' => 602,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getQualifiedForeignKeyName' => 
      array (
        'name' => 'getQualifiedForeignKeyName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the foreign key for the relationship.
 *
 * @return string
 */',
        'startLine' => 609,
        'endLine' => 612,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'aliasName' => NULL,
      ),
      'getLocalKeyName' => 
      array (
        'name' => 'getLocalKeyName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the local key for the relationship.
 *
 * @return string
 */',
        'startLine' => 619,
        'endLine' => 622,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\HasOneOrMany',
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