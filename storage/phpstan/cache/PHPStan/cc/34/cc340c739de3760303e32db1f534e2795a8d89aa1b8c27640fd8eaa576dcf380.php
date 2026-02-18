<?php declare(strict_types = 1);

// odsl-/Users/fabianwesner/Herd/shop/app/Livewire/Admin/Customers/Show.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Livewire\Admin\Customers\Show
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.65.0.9-8.4.17-d364c717eb00e3480825568a382b7aee353cb8a53f491ca0d221b9ca1e84c9f0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Livewire\\Admin\\Customers\\Show',
        'filename' => '/Users/fabianwesner/Herd/shop/app/Livewire/Admin/Customers/Show.php',
      ),
    ),
    'namespace' => 'App\\Livewire\\Admin\\Customers',
    'name' => 'App\\Livewire\\Admin\\Customers\\Show',
    'shortName' => 'Show',
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
              'startLine' => 10,
              'endLine' => 10,
              'startTokenPos' => 30,
              'startFilePos' => 170,
              'endTokenPos' => 30,
              'endFilePos' => 184,
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
            'code' => '\'Customer Detail\'',
            'attributes' => 
            array (
              'startLine' => 11,
              'endLine' => 11,
              'startTokenPos' => 37,
              'startFilePos' => 196,
              'endTokenPos' => 37,
              'endFilePos' => 212,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 10,
    'endLine' => 96,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Livewire\\Component',
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
      'customer' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'name' => 'customer',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Models\\Customer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'addressForm' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'name' => 'addressForm',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[\'label\' => \'\', \'line1\' => \'\', \'line2\' => \'\', \'city\' => \'\', \'state\' => \'\', \'zip\' => \'\', \'country\' => \'\']',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 25,
            'startTokenPos' => 68,
            'startFilePos' => 437,
            'endTokenPos' => 119,
            'endFilePos' => 603,
          ),
        ),
        'docComment' => '/** @var array{label: string, line1: string, line2: string, city: string, state: string, zip: string, country: string} */',
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'editingAddressId' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'name' => 'editingAddressId',
        'modifiers' => 1,
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
                  'name' => 'int',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 131,
            'startFilePos' => 643,
            'endTokenPos' => 131,
            'endFilePos' => 646,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 41,
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
      'mount' => 
      array (
        'name' => 'mount',
        'parameters' => 
        array (
          'customer' => 
          array (
            'name' => 'customer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Customer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 27,
            'endColumn' => 44,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Customers',
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'currentClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'aliasName' => NULL,
      ),
      'openAddressForm' => 
      array (
        'name' => 'openAddressForm',
        'parameters' => 
        array (
          'addressId' => 
          array (
            'name' => 'addressId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 35,
                'endLine' => 35,
                'startTokenPos' => 195,
                'startFilePos' => 882,
                'endTokenPos' => 195,
                'endFilePos' => 885,
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
                      'name' => 'int',
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 37,
            'endColumn' => 58,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Customers',
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'currentClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'aliasName' => NULL,
      ),
      'saveAddress' => 
      array (
        'name' => 'saveAddress',
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
        'startLine' => 57,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Customers',
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'currentClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'aliasName' => NULL,
      ),
      'deleteAddress' => 
      array (
        'name' => 'deleteAddress',
        'parameters' => 
        array (
          'addressId' => 
          array (
            'name' => 'addressId',
            'default' => NULL,
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
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 35,
            'endColumn' => 48,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 82,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Customers',
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'currentClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
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
        'startLine' => 92,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Customers',
        'declaringClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'implementingClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
        'currentClassName' => 'App\\Livewire\\Admin\\Customers\\Show',
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