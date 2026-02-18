<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Contracts/Auth/SupportsBasicAuth.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Contracts\Auth\SupportsBasicAuth
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-efc0d2b713ff72df786cbc3b5a8a642b43e52d019570c68d1e4b9ae167b0ed77-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Contracts\\Auth\\SupportsBasicAuth',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Contracts/Auth/SupportsBasicAuth.php',
      ),
    ),
    'namespace' => 'Illuminate\\Contracts\\Auth',
    'name' => 'Illuminate\\Contracts\\Auth\\SupportsBasicAuth',
    'shortName' => 'SupportsBasicAuth',
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
    'endLine' => 24,
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
      'basic' => 
      array (
        'name' => 'basic',
        'parameters' => 
        array (
          'field' => 
          array (
            'name' => 'field',
            'default' => 
            array (
              'code' => '\'email\'',
              'attributes' => 
              array (
                'startLine' => 14,
                'endLine' => 14,
                'startTokenPos' => 25,
                'startFilePos' => 319,
                'endTokenPos' => 25,
                'endFilePos' => 325,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'extraConditions' => 
          array (
            'name' => 'extraConditions',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 14,
                'endLine' => 14,
                'startTokenPos' => 32,
                'startFilePos' => 347,
                'endTokenPos' => 33,
                'endFilePos' => 348,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 45,
            'endColumn' => 65,
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
 * Attempt to authenticate using HTTP Basic Auth.
 *
 * @param  string  $field
 * @param  array  $extraConditions
 * @return \\Symfony\\Component\\HttpFoundation\\Response|null
 */',
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 67,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Auth',
        'declaringClassName' => 'Illuminate\\Contracts\\Auth\\SupportsBasicAuth',
        'implementingClassName' => 'Illuminate\\Contracts\\Auth\\SupportsBasicAuth',
        'currentClassName' => 'Illuminate\\Contracts\\Auth\\SupportsBasicAuth',
        'aliasName' => NULL,
      ),
      'onceBasic' => 
      array (
        'name' => 'onceBasic',
        'parameters' => 
        array (
          'field' => 
          array (
            'name' => 'field',
            'default' => 
            array (
              'code' => '\'email\'',
              'attributes' => 
              array (
                'startLine' => 23,
                'endLine' => 23,
                'startTokenPos' => 49,
                'startFilePos' => 600,
                'endTokenPos' => 49,
                'endFilePos' => 606,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 31,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'extraConditions' => 
          array (
            'name' => 'extraConditions',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 23,
                'endLine' => 23,
                'startTokenPos' => 56,
                'startFilePos' => 628,
                'endTokenPos' => 57,
                'endFilePos' => 629,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 49,
            'endColumn' => 69,
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
 * Perform a stateless HTTP Basic login attempt.
 *
 * @param  string  $field
 * @param  array  $extraConditions
 * @return \\Symfony\\Component\\HttpFoundation\\Response|null
 */',
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 71,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Contracts\\Auth',
        'declaringClassName' => 'Illuminate\\Contracts\\Auth\\SupportsBasicAuth',
        'implementingClassName' => 'Illuminate\\Contracts\\Auth\\SupportsBasicAuth',
        'currentClassName' => 'Illuminate\\Contracts\\Auth\\SupportsBasicAuth',
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