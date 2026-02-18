<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Auth/EloquentUserProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Auth\EloquentUserProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6c7268a984f2a4de0290cd69e4ca1d43e76a874c88b96c591dcd3134d9e2fc2c-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Auth\\EloquentUserProvider',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Auth/EloquentUserProvider.php',
      ),
    ),
    'namespace' => 'Illuminate\\Auth',
    'name' => 'Illuminate\\Auth\\EloquentUserProvider',
    'shortName' => 'EloquentUserProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 279,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Auth\\UserProvider',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'hasher' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'name' => 'hasher',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The hasher implementation.
 *
 * @var \\Illuminate\\Contracts\\Hashing\\Hasher
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'model' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'name' => 'model',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The Eloquent user model.
 *
 * @var class-string<\\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'queryCallback' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'name' => 'queryCallback',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The callback that may modify the user retrieval queries.
 *
 * @var (\\Closure(\\Illuminate\\Database\\Eloquent\\Builder<*>):mixed)|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
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
          'hasher' => 
          array (
            'name' => 'hasher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Hashing\\Hasher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 33,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'model' => 
          array (
            'name' => 'model',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 57,
            'endColumn' => 62,
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
 * Create a new database user provider.
 *
 * @param  \\Illuminate\\Contracts\\Hashing\\Hasher  $hasher
 * @param  string  $model
 */',
        'startLine' => 40,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'retrieveById' => 
      array (
        'name' => 'retrieveById',
        'parameters' => 
        array (
          'identifier' => 
          array (
            'name' => 'identifier',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 34,
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
 * Retrieve a user by their unique identifier.
 *
 * @param  mixed  $identifier
 * @return (\\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model)|null
 */',
        'startLine' => 52,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'retrieveByToken' => 
      array (
        'name' => 'retrieveByToken',
        'parameters' => 
        array (
          'identifier' => 
          array (
            'name' => 'identifier',
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
            'startColumn' => 37,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'token' => 
          array (
            'name' => 'token',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 50,
            'endColumn' => 78,
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
 * Retrieve a user by their unique identifier and "remember me" token.
 *
 * @param  mixed  $identifier
 * @param  string  $token
 * @return (\\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model)|null
 */',
        'startLine' => 68,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'updateRememberToken' => 
      array (
        'name' => 'updateRememberToken',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 41,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'token' => 
          array (
            'name' => 'token',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 61,
            'endColumn' => 89,
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
 * Update the "remember me" token for the given user in storage.
 *
 * @param  \\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model  $user
 * @param  string  $token
 * @return void
 */',
        'startLine' => 92,
        'endLine' => 103,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'retrieveByCredentials' => 
      array (
        'name' => 'retrieveByCredentials',
        'parameters' => 
        array (
          'credentials' => 
          array (
            'name' => 'credentials',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 43,
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
 * Retrieve a user by the given credentials.
 *
 * @param  array  $credentials
 * @return (\\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model)|null
 */',
        'startLine' => 111,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'validateCredentials' => 
      array (
        'name' => 'validateCredentials',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 41,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'credentials' => 
          array (
            'name' => 'credentials',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 61,
            'endColumn' => 101,
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
 * Validate a user against the given credentials.
 *
 * @param  \\Illuminate\\Contracts\\Auth\\Authenticatable  $user
 * @param  array  $credentials
 * @return bool
 */',
        'startLine' => 148,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'rehashPasswordIfRequired' => 
      array (
        'name' => 'rehashPasswordIfRequired',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 46,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'credentials' => 
          array (
            'name' => 'credentials',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 66,
            'endColumn' => 106,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'force' => 
          array (
            'name' => 'force',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 169,
                'endLine' => 169,
                'startTokenPos' => 645,
                'startFilePos' => 4995,
                'endTokenPos' => 645,
                'endFilePos' => 4999,
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
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 109,
            'endColumn' => 127,
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
 * Rehash the user\'s password if required and supported.
 *
 * @param  \\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model  $user
 * @param  array  $credentials
 * @param  bool  $force
 * @return void
 */',
        'startLine' => 169,
        'endLine' => 178,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'newModelQuery' => 
      array (
        'name' => 'newModelQuery',
        'parameters' => 
        array (
          'model' => 
          array (
            'name' => 'model',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 188,
                'endLine' => 188,
                'startTokenPos' => 731,
                'startFilePos' => 5556,
                'endTokenPos' => 731,
                'endFilePos' => 5559,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 38,
            'endColumn' => 50,
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
 * Get a new query builder for the model instance.
 *
 * @template TModel of \\Illuminate\\Database\\Eloquent\\Model
 *
 * @param  TModel|null  $model
 * @return \\Illuminate\\Database\\Eloquent\\Builder<TModel>
 */',
        'startLine' => 188,
        'endLine' => 197,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'createModel' => 
      array (
        'name' => 'createModel',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new instance of the model.
 *
 * @return \\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model
 */',
        'startLine' => 204,
        'endLine' => 209,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'getHasher' => 
      array (
        'name' => 'getHasher',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the hasher implementation.
 *
 * @return \\Illuminate\\Contracts\\Hashing\\Hasher
 */',
        'startLine' => 216,
        'endLine' => 219,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'setHasher' => 
      array (
        'name' => 'setHasher',
        'parameters' => 
        array (
          'hasher' => 
          array (
            'name' => 'hasher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Hashing\\Hasher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 227,
            'endLine' => 227,
            'startColumn' => 31,
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
 * Sets the hasher implementation.
 *
 * @param  \\Illuminate\\Contracts\\Hashing\\Hasher  $hasher
 * @return $this
 */',
        'startLine' => 227,
        'endLine' => 232,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'getModel' => 
      array (
        'name' => 'getModel',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the name of the Eloquent user model.
 *
 * @return class-string<\\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model>
 */',
        'startLine' => 239,
        'endLine' => 242,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'setModel' => 
      array (
        'name' => 'setModel',
        'parameters' => 
        array (
          'model' => 
          array (
            'name' => 'model',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 250,
            'endLine' => 250,
            'startColumn' => 30,
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
 * Sets the name of the Eloquent user model.
 *
 * @param  class-string<\\Illuminate\\Contracts\\Auth\\Authenticatable&\\Illuminate\\Database\\Eloquent\\Model>  $model
 * @return $this
 */',
        'startLine' => 250,
        'endLine' => 255,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'getQueryCallback' => 
      array (
        'name' => 'getQueryCallback',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the callback that modifies the query before retrieving users.
 *
 * @return (\\Closure(\\Illuminate\\Database\\Eloquent\\Builder<*>):mixed)|null
 */',
        'startLine' => 262,
        'endLine' => 265,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'aliasName' => NULL,
      ),
      'withQuery' => 
      array (
        'name' => 'withQuery',
        'parameters' => 
        array (
          'queryCallback' => 
          array (
            'name' => 'queryCallback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 273,
                'endLine' => 273,
                'startTokenPos' => 957,
                'startFilePos' => 7584,
                'endTokenPos' => 957,
                'endFilePos' => 7587,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 273,
            'endLine' => 273,
            'startColumn' => 31,
            'endColumn' => 51,
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
 * Sets the callback to modify the query before retrieving users.
 *
 * @param  (\\Closure(\\Illuminate\\Database\\Eloquent\\Builder<*>):mixed)|null  $queryCallback
 * @return $this
 */',
        'startLine' => 273,
        'endLine' => 278,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'implementingClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
        'currentClassName' => 'Illuminate\\Auth\\EloquentUserProvider',
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