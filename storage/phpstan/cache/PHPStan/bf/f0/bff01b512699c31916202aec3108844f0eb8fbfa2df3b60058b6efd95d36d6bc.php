<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Http/JsonResponse.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Http\JsonResponse
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-16af69dd3516d8e0fef6a7421919dca586748d10f0a5434937c56c31d5cb2d66-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Http\\JsonResponse',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Http/JsonResponse.php',
      ),
    ),
    'namespace' => 'Illuminate\\Http',
    'name' => 'Illuminate\\Http\\JsonResponse',
    'shortName' => 'JsonResponse',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 140,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Symfony\\Component\\HttpFoundation\\JsonResponse',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Http\\ResponseTrait',
      1 => 'Illuminate\\Support\\Traits\\Macroable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 83,
                'startFilePos' => 665,
                'endTokenPos' => 83,
                'endFilePos' => 668,
              ),
            ),
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
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'status' => 
          array (
            'name' => 'status',
            'default' => 
            array (
              'code' => '200',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 90,
                'startFilePos' => 681,
                'endTokenPos' => 90,
                'endFilePos' => 683,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 47,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'headers' => 
          array (
            'name' => 'headers',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 97,
                'startFilePos' => 697,
                'endTokenPos' => 98,
                'endFilePos' => 698,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 62,
            'endColumn' => 74,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 105,
                'startFilePos' => 712,
                'endTokenPos' => 105,
                'endFilePos' => 712,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 77,
            'endColumn' => 88,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'json' => 
          array (
            'name' => 'json',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 112,
                'startFilePos' => 723,
                'endTokenPos' => 112,
                'endFilePos' => 727,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 91,
            'endColumn' => 103,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new JSON response instance.
 *
 * @param  mixed  $data
 * @param  int  $status
 * @param  array  $headers
 * @param  int  $options
 * @param  bool  $json
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
        'namespace' => 'Illuminate\\Http',
        'declaringClassName' => 'Illuminate\\Http\\JsonResponse',
        'implementingClassName' => 'Illuminate\\Http\\JsonResponse',
        'currentClassName' => 'Illuminate\\Http\\JsonResponse',
        'aliasName' => NULL,
      ),
      'fromJsonString' => 
      array (
        'name' => 'fromJsonString',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 166,
                'startFilePos' => 990,
                'endTokenPos' => 166,
                'endFilePos' => 993,
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
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 43,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'status' => 
          array (
            'name' => 'status',
            'default' => 
            array (
              'code' => '200',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 175,
                'startFilePos' => 1010,
                'endTokenPos' => 175,
                'endFilePos' => 1012,
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
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 65,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'headers' => 
          array (
            'name' => 'headers',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 184,
                'startFilePos' => 1032,
                'endTokenPos' => 185,
                'endFilePos' => 1033,
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
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 84,
            'endColumn' => 102,
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
            'name' => 'static',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritdoc}
 *
 * @return static
 */',
        'startLine' => 39,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Http',
        'declaringClassName' => 'Illuminate\\Http\\JsonResponse',
        'implementingClassName' => 'Illuminate\\Http\\JsonResponse',
        'currentClassName' => 'Illuminate\\Http\\JsonResponse',
        'aliasName' => NULL,
      ),
      'withCallback' => 
      array (
        'name' => 'withCallback',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 51,
                'endLine' => 51,
                'startTokenPos' => 229,
                'startFilePos' => 1278,
                'endTokenPos' => 229,
                'endFilePos' => 1281,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 34,
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
 * Sets the JSONP callback.
 *
 * @param  string|null  $callback
 * @return $this
 */',
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http',
        'declaringClassName' => 'Illuminate\\Http\\JsonResponse',
        'implementingClassName' => 'Illuminate\\Http\\JsonResponse',
        'currentClassName' => 'Illuminate\\Http\\JsonResponse',
        'aliasName' => NULL,
      ),
      'getData' => 
      array (
        'name' => 'getData',
        'parameters' => 
        array (
          'assoc' => 
          array (
            'name' => 'assoc',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 63,
                'endLine' => 63,
                'startTokenPos' => 258,
                'startFilePos' => 1531,
                'endTokenPos' => 258,
                'endFilePos' => 1535,
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
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'depth' => 
          array (
            'name' => 'depth',
            'default' => 
            array (
              'code' => '512',
              'attributes' => 
              array (
                'startLine' => 63,
                'endLine' => 63,
                'startTokenPos' => 265,
                'startFilePos' => 1547,
                'endTokenPos' => 265,
                'endFilePos' => 1549,
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
            'startColumn' => 45,
            'endColumn' => 56,
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
 * Get the json_decoded data from the response.
 *
 * @param  bool  $assoc
 * @param  int  $depth
 * @return mixed
 */',
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http',
        'declaringClassName' => 'Illuminate\\Http\\JsonResponse',
        'implementingClassName' => 'Illuminate\\Http\\JsonResponse',
        'currentClassName' => 'Illuminate\\Http\\JsonResponse',
        'aliasName' => NULL,
      ),
      'setData' => 
      array (
        'name' => 'setData',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 76,
                'endLine' => 76,
                'startTokenPos' => 304,
                'startFilePos' => 1789,
                'endTokenPos' => 305,
                'endFilePos' => 1790,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 29,
            'endColumn' => 38,
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
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritdoc}
 *
 * @return static
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 75,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http',
        'declaringClassName' => 'Illuminate\\Http\\JsonResponse',
        'implementingClassName' => 'Illuminate\\Http\\JsonResponse',
        'currentClassName' => 'Illuminate\\Http\\JsonResponse',
        'aliasName' => NULL,
      ),
      'hasValidJson' => 
      array (
        'name' => 'hasValidJson',
        'parameters' => 
        array (
          'jsonError' => 
          array (
            'name' => 'jsonError',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 37,
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
 * Determine if an error occurred during JSON encoding.
 *
 * @param  int  $jsonError
 * @return bool
 */',
        'startLine' => 103,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Http',
        'declaringClassName' => 'Illuminate\\Http\\JsonResponse',
        'implementingClassName' => 'Illuminate\\Http\\JsonResponse',
        'currentClassName' => 'Illuminate\\Http\\JsonResponse',
        'aliasName' => NULL,
      ),
      'setEncodingOptions' => 
      array (
        'name' => 'setEncodingOptions',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
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
            'startColumn' => 40,
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
            'name' => 'static',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritdoc}
 *
 * @return static
 */',
        'startLine' => 122,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http',
        'declaringClassName' => 'Illuminate\\Http\\JsonResponse',
        'implementingClassName' => 'Illuminate\\Http\\JsonResponse',
        'currentClassName' => 'Illuminate\\Http\\JsonResponse',
        'aliasName' => NULL,
      ),
      'hasEncodingOption' => 
      array (
        'name' => 'hasEncodingOption',
        'parameters' => 
        array (
          'option' => 
          array (
            'name' => 'option',
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
            'startColumn' => 39,
            'endColumn' => 45,
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
 * Determine if a JSON encoding option is set.
 *
 * @param  int  $option
 * @return bool
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
        'namespace' => 'Illuminate\\Http',
        'declaringClassName' => 'Illuminate\\Http\\JsonResponse',
        'implementingClassName' => 'Illuminate\\Http\\JsonResponse',
        'currentClassName' => 'Illuminate\\Http\\JsonResponse',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
        'Illuminate\\Support\\Traits\\Macroable' => 
        array (
          0 => 
          array (
            'alias' => 'macroCall',
            'method' => '__call',
            'hash' => 'illuminate\\support\\traits\\macroable::__call',
          ),
        ),
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
        'illuminate\\support\\traits\\macroable::__call' => 'Illuminate\\Support\\Traits\\Macroable::__call',
      ),
    ),
  ),
));