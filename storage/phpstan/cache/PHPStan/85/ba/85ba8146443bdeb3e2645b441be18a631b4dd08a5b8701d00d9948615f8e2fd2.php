<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Auth/CreatesUserProviders.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Auth\CreatesUserProviders
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-1b7f7a1bf26126093d55d26f385663fd567c9292f8c25a80de78dc84c0a22ff0-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Auth\\CreatesUserProviders',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Auth/CreatesUserProviders.php',
      ),
    ),
    'namespace' => 'Illuminate\\Auth',
    'name' => 'Illuminate\\Auth\\CreatesUserProviders',
    'shortName' => 'CreatesUserProviders',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 7,
    'endLine' => 93,
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
      'customProviderCreators' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'implementingClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'name' => 'customProviderCreators',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 26,
            'startFilePos' => 224,
            'endTokenPos' => 27,
            'endFilePos' => 225,
          ),
        ),
        'docComment' => '/**
 * The registered custom provider creators.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 43,
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
      'createUserProvider' => 
      array (
        'name' => 'createUserProvider',
        'parameters' => 
        array (
          'provider' => 
          array (
            'name' => 'provider',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 24,
                'endLine' => 24,
                'startTokenPos' => 42,
                'startFilePos' => 512,
                'endTokenPos' => 42,
                'endFilePos' => 515,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 40,
            'endColumn' => 55,
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
 * Create the user provider implementation for the driver.
 *
 * @param  string|null  $provider
 * @return \\Illuminate\\Contracts\\Auth\\UserProvider|null
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 24,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'implementingClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'currentClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'aliasName' => NULL,
      ),
      'getProviderConfiguration' => 
      array (
        'name' => 'getProviderConfiguration',
        'parameters' => 
        array (
          'provider' => 
          array (
            'name' => 'provider',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 49,
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
 * Get the user provider configuration.
 *
 * @param  string|null  $provider
 * @return array|null
 */',
        'startLine' => 51,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'implementingClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'currentClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'aliasName' => NULL,
      ),
      'createDatabaseProvider' => 
      array (
        'name' => 'createDatabaseProvider',
        'parameters' => 
        array (
          'config' => 
          array (
            'name' => 'config',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 47,
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
 * Create an instance of the database user provider.
 *
 * @param  array  $config
 * @return \\Illuminate\\Auth\\DatabaseUserProvider
 */',
        'startLine' => 64,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'implementingClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'currentClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'aliasName' => NULL,
      ),
      'createEloquentProvider' => 
      array (
        'name' => 'createEloquentProvider',
        'parameters' => 
        array (
          'config' => 
          array (
            'name' => 'config',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 47,
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
 * Create an instance of the Eloquent user provider.
 *
 * @param  array  $config
 * @return \\Illuminate\\Auth\\EloquentUserProvider
 */',
        'startLine' => 79,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'implementingClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'currentClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'aliasName' => NULL,
      ),
      'getDefaultUserProvider' => 
      array (
        'name' => 'getDefaultUserProvider',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default user provider name.
 *
 * @return string
 */',
        'startLine' => 89,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'implementingClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
        'currentClassName' => 'Illuminate\\Auth\\CreatesUserProviders',
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