<?php declare(strict_types = 1);

// odsl-/Users/fabianwesner/Herd/shop/app/Livewire/Admin/Customers/Index.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Livewire\Admin\Customers\Index
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.65.0.9-8.4.17-d260fb3a2483db9fb89180f1c8c410e3d4627de9afae75524d9cacd2f3238b2f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Livewire\\Admin\\Customers\\Index',
        'filename' => '/Users/fabianwesner/Herd/shop/app/Livewire/Admin/Customers/Index.php',
      ),
    ),
    'namespace' => 'App\\Livewire\\Admin\\Customers',
    'name' => 'App\\Livewire\\Admin\\Customers\\Index',
    'shortName' => 'Index',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'Livewire\\Attributes\\Layout',
        'isRepeated' => false,
        'arguments' => 
        array (
          0 => 
          array (
            'code' => '\'layouts.admin\'',
            'attributes' => 
            array (
              'startLine' => 14,
              'endLine' => 14,
              'startTokenPos' => 50,
              'startFilePos' => 320,
              'endTokenPos' => 50,
              'endFilePos' => 334,
            ),
          ),
        ),
      ),
      1 => 
      array (
        'name' => 'Livewire\\Attributes\\Title',
        'isRepeated' => false,
        'arguments' => 
        array (
          0 => 
          array (
            'code' => '\'Customers\'',
            'attributes' => 
            array (
              'startLine' => 15,
              'endLine' => 15,
              'startTokenPos' => 57,
              'startFilePos' => 346,
              'endTokenPos' => 57,
              'endFilePos' => 356,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 14,
    'endLine' => 52,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Livewire\\Component',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Livewire\\WithPagination',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'search' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'name' => 'search',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 88,
            'startFilePos' => 456,
            'endTokenPos' => 88,
            'endFilePos' => 457,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Url',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'startLine' => 20,
        'endLine' => 21,
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
      'updatedSearch' => 
      array (
        'name' => 'updatedSearch',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Customers',
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'currentClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'aliasName' => NULL,
      ),
      'customers' => 
      array (
        'name' => 'customers',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\Pagination\\LengthAwarePaginator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Computed',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * @return LengthAwarePaginator<Customer>
 */',
        'startLine' => 31,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Customers',
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'currentClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 48,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Customers',
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
        'currentClassName' => 'App\\Livewire\\Admin\\Customers\\Index',
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