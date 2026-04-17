<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Hashing/BcryptHasher.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Hashing\BcryptHasher
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-03832503431d2646bfc05305d2184bdc276f16a4fbb7fc3f9677492d4bb37c70-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Hashing\\BcryptHasher',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Hashing/BcryptHasher.php',
      ),
    ),
    'namespace' => 'Illuminate\\Hashing',
    'name' => 'Illuminate\\Hashing\\BcryptHasher',
    'shortName' => 'BcryptHasher',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 174,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Hashing\\AbstractHasher',
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Hashing\\Hasher',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'rounds' => 
      array (
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'name' => 'rounds',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '12',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 53,
            'startFilePos' => 326,
            'endTokenPos' => 53,
            'endFilePos' => 327,
          ),
        ),
        'docComment' => '/**
 * The default cost factor.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'verifyAlgorithm' => 
      array (
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'name' => 'verifyAlgorithm',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 64,
            'startFilePos' => 460,
            'endTokenPos' => 64,
            'endFilePos' => 464,
          ),
        ),
        'docComment' => '/**
 * Indicates whether to perform an algorithm check.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'limit' => 
      array (
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'name' => 'limit',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The maximum allowed length of strings that can be hashed.
 *
 * @var int|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 21,
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
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 38,
                'endLine' => 38,
                'startTokenPos' => 88,
                'startFilePos' => 740,
                'endTokenPos' => 89,
                'endFilePos' => 741,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 33,
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
 * Create a new hasher instance.
 *
 * @param  array  $options
 */',
        'startLine' => 38,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'aliasName' => NULL,
      ),
      'make' => 
      array (
        'name' => 'make',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 26,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 55,
                'endLine' => 55,
                'startTokenPos' => 171,
                'startFilePos' => 1244,
                'endTokenPos' => 172,
                'endFilePos' => 1245,
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
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 57,
            'endColumn' => 75,
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
 * Hash the given value.
 *
 * @param  string  $value
 * @param  array  $options
 * @return string
 *
 * @throws \\RuntimeException
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 55,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'aliasName' => NULL,
      ),
      'check' => 
      array (
        'name' => 'check',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 27,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'hashedValue' => 
          array (
            'name' => 'hashedValue',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 58,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 82,
                'endLine' => 82,
                'startTokenPos' => 305,
                'startFilePos' => 2062,
                'endTokenPos' => 306,
                'endFilePos' => 2063,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 72,
            'endColumn' => 90,
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
 * Check the given plain value against a hash.
 *
 * @param  string  $value
 * @param  string  $hashedValue
 * @param  array  $options
 * @return bool
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 82,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'aliasName' => NULL,
      ),
      'needsRehash' => 
      array (
        'name' => 'needsRehash',
        'parameters' => 
        array (
          'hashedValue' => 
          array (
            'name' => 'hashedValue',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 408,
                'startFilePos' => 2681,
                'endTokenPos' => 409,
                'endFilePos' => 2682,
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 47,
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
 * Check if the given hash has been hashed using the given options.
 *
 * @param  string  $hashedValue
 * @param  array  $options
 * @return bool
 */',
        'startLine' => 102,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'aliasName' => NULL,
      ),
      'verifyConfiguration' => 
      array (
        'name' => 'verifyConfiguration',
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
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 41,
            'endColumn' => 46,
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
 * Verifies that the configuration is less than or equal to what is configured.
 *
 * @internal
 */',
        'startLine' => 114,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'aliasName' => NULL,
      ),
      'isUsingCorrectAlgorithm' => 
      array (
        'name' => 'isUsingCorrectAlgorithm',
        'parameters' => 
        array (
          'hashedValue' => 
          array (
            'name' => 'hashedValue',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 48,
            'endColumn' => 59,
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
 * Verify the hashed value\'s algorithm.
 *
 * @param  string  $hashedValue
 * @return bool
 */',
        'startLine' => 125,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'aliasName' => NULL,
      ),
      'isUsingValidOptions' => 
      array (
        'name' => 'isUsingValidOptions',
        'parameters' => 
        array (
          'hashedValue' => 
          array (
            'name' => 'hashedValue',
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
            'startColumn' => 44,
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
 * Verify the hashed value\'s options.
 *
 * @param  string  $hashedValue
 * @return bool
 */',
        'startLine' => 136,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'aliasName' => NULL,
      ),
      'setRounds' => 
      array (
        'name' => 'setRounds',
        'parameters' => 
        array (
          'rounds' => 
          array (
            'name' => 'rounds',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 31,
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
 * Set the default password work factor.
 *
 * @param  int  $rounds
 * @return $this
 */',
        'startLine' => 157,
        'endLine' => 162,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'aliasName' => NULL,
      ),
      'cost' => 
      array (
        'name' => 'cost',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 170,
                'endLine' => 170,
                'startTokenPos' => 644,
                'startFilePos' => 4216,
                'endTokenPos' => 645,
                'endFilePos' => 4217,
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
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 29,
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
 * Extract the cost value from the options array.
 *
 * @param  array  $options
 * @return int
 */',
        'startLine' => 170,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Hashing',
        'declaringClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'implementingClassName' => 'Illuminate\\Hashing\\BcryptHasher',
        'currentClassName' => 'Illuminate\\Hashing\\BcryptHasher',
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