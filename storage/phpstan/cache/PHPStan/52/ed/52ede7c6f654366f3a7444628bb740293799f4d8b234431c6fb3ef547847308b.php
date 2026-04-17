<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Http/Resources/Json/ResourceCollection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Http\Resources\Json\ResourceCollection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-18bbd200e0c7bbde2cf70101864517f4a2839e90aeaa9b881f3449af2f305f3b-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Http/Resources/Json/ResourceCollection.php',
      ),
    ),
    'namespace' => 'Illuminate\\Http\\Resources\\Json',
    'name' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
    'shortName' => 'ResourceCollection',
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
    'parentClassName' => 'Illuminate\\Http\\Resources\\Json\\JsonResource',
    'implementsClassNames' => 
    array (
      0 => 'Countable',
      1 => 'IteratorAggregate',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Http\\Resources\\CollectsResources',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'collects' => 
      array (
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'name' => 'collects',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The resource that this resource collects.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'collection' => 
      array (
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'name' => 'collection',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The mapped collection instance.
 *
 * @var \\Illuminate\\Support\\Collection|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'preserveAllQueryParameters' => 
      array (
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'name' => 'preserveAllQueryParameters',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 81,
            'startFilePos' => 807,
            'endTokenPos' => 81,
            'endFilePos' => 811,
          ),
        ),
        'docComment' => '/**
 * Indicates if all existing request query parameters should be added to pagination links.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 50,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'queryParameters' => 
      array (
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'name' => 'queryParameters',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The query parameters that should be added to the pagination links.
 *
 * @var array|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 31,
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
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 33,
            'endColumn' => 41,
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
 * Create a new resource instance.
 *
 * @param  mixed  $resource
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
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'aliasName' => NULL,
      ),
      'preserveQuery' => 
      array (
        'name' => 'preserveQuery',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that all current query parameters should be appended to pagination links.
 *
 * @return $this
 */',
        'startLine' => 61,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'aliasName' => NULL,
      ),
      'withQuery' => 
      array (
        'name' => 'withQuery',
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 31,
            'endColumn' => 42,
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
 * Specify the query string parameters that should be present on pagination links.
 *
 * @param  array  $query
 * @return $this
 */',
        'startLine' => 74,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'aliasName' => NULL,
      ),
      'count' => 
      array (
        'name' => 'count',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the count of items in the resource collection.
 *
 * @return int
 */',
        'startLine' => 88,
        'endLine' => 91,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'aliasName' => NULL,
      ),
      'toArray' => 
      array (
        'name' => 'toArray',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
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
 * Transform the resource into a JSON array.
 *
 * @param  \\Illuminate\\Http\\Request  $request
 * @return array|\\Illuminate\\Contracts\\Support\\Arrayable|\\JsonSerializable
 */',
        'startLine' => 99,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'aliasName' => NULL,
      ),
      'toResponse' => 
      array (
        'name' => 'toResponse',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 32,
            'endColumn' => 39,
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
 * Create an HTTP response that represents the object.
 *
 * @param  \\Illuminate\\Http\\Request  $request
 * @return \\Illuminate\\Http\\JsonResponse
 */',
        'startLine' => 115,
        'endLine' => 122,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'aliasName' => NULL,
      ),
      'preparePaginatedResponse' => 
      array (
        'name' => 'preparePaginatedResponse',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 130,
            'endLine' => 130,
            'startColumn' => 49,
            'endColumn' => 56,
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
 * Create a paginate-aware HTTP response.
 *
 * @param  \\Illuminate\\Http\\Request  $request
 * @return \\Illuminate\\Http\\JsonResponse
 */',
        'startLine' => 130,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Http\\Resources\\Json',
        'declaringClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'implementingClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
        'currentClassName' => 'Illuminate\\Http\\Resources\\Json\\ResourceCollection',
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