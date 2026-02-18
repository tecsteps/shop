<?php declare(strict_types = 1);

// odsl-/Users/fabianwesner/Herd/shop/app/Livewire/Admin/Products/Form.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Livewire\Admin\Products\Form
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.65.0.9-8.4.17-74315b8113b72dd14c9082e51e64633e25033e4c8340844dcd84e3977b0cb170',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Livewire\\Admin\\Products\\Form',
        'filename' => '/Users/fabianwesner/Herd/shop/app/Livewire/Admin/Products/Form.php',
      ),
    ),
    'namespace' => 'App\\Livewire\\Admin\\Products',
    'name' => 'App\\Livewire\\Admin\\Products\\Form',
    'shortName' => 'Form',
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
              'startLine' => 15,
              'endLine' => 15,
              'startTokenPos' => 55,
              'startFilePos' => 320,
              'endTokenPos' => 55,
              'endFilePos' => 334,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 15,
    'endLine' => 326,
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
      'product' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'product',
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
                  'name' => 'App\\Models\\Product',
                  'isIdentifier' => false,
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
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 78,
            'startFilePos' => 400,
            'endTokenPos' => 78,
            'endFilePos' => 403,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'title' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'title',
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
            'startTokenPos' => 96,
            'startFilePos' => 477,
            'endTokenPos' => 96,
            'endFilePos' => 478,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Validate',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'required|string|max:255\'',
                'attributes' => 
                array (
                  'startLine' => 20,
                  'endLine' => 20,
                  'startTokenPos' => 84,
                  'startFilePos' => 422,
                  'endTokenPos' => 84,
                  'endFilePos' => 446,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 20,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'descriptionHtml' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'descriptionHtml',
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
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 114,
            'startFilePos' => 564,
            'endTokenPos' => 114,
            'endFilePos' => 565,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Validate',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'nullable|string|max:65535\'',
                'attributes' => 
                array (
                  'startLine' => 23,
                  'endLine' => 23,
                  'startTokenPos' => 102,
                  'startFilePos' => 497,
                  'endTokenPos' => 102,
                  'endFilePos' => 523,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 23,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'status' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'status',
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
          'code' => '\'draft\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 132,
            'startFilePos' => 657,
            'endTokenPos' => 132,
            'endFilePos' => 663,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Validate',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'required|string|in:draft,active,archived\'',
                'attributes' => 
                array (
                  'startLine' => 26,
                  'endLine' => 26,
                  'startTokenPos' => 120,
                  'startFilePos' => 584,
                  'endTokenPos' => 120,
                  'endFilePos' => 625,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 26,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'vendor' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'vendor',
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
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 150,
            'startFilePos' => 738,
            'endTokenPos' => 150,
            'endFilePos' => 739,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Validate',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'nullable|string|max:255\'',
                'attributes' => 
                array (
                  'startLine' => 29,
                  'endLine' => 29,
                  'startTokenPos' => 138,
                  'startFilePos' => 682,
                  'endTokenPos' => 138,
                  'endFilePos' => 706,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 29,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'productType' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'productType',
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
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 168,
            'startFilePos' => 819,
            'endTokenPos' => 168,
            'endFilePos' => 820,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Validate',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'nullable|string|max:255\'',
                'attributes' => 
                array (
                  'startLine' => 32,
                  'endLine' => 32,
                  'startTokenPos' => 156,
                  'startFilePos' => 758,
                  'endTokenPos' => 156,
                  'endFilePos' => 782,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 32,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'tags' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'tags',
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
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 186,
            'startFilePos' => 885,
            'endTokenPos' => 186,
            'endFilePos' => 886,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Validate',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'nullable|string\'',
                'attributes' => 
                array (
                  'startLine' => 35,
                  'endLine' => 35,
                  'startTokenPos' => 174,
                  'startFilePos' => 839,
                  'endTokenPos' => 174,
                  'endFilePos' => 855,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 35,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'handle' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'handle',
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
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 204,
            'startFilePos' => 961,
            'endTokenPos' => 204,
            'endFilePos' => 962,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Validate',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'required|string|max:255\'',
                'attributes' => 
                array (
                  'startLine' => 38,
                  'endLine' => 38,
                  'startTokenPos' => 192,
                  'startFilePos' => 905,
                  'endTokenPos' => 192,
                  'endFilePos' => 929,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 38,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'publishedAt' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'publishedAt',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 216,
            'startFilePos' => 1000,
            'endTokenPos' => 216,
            'endFilePos' => 1003,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'collectionIds' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'collectionIds',
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
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 229,
            'startFilePos' => 1068,
            'endTokenPos' => 230,
            'endFilePos' => 1069,
          ),
        ),
        'docComment' => '/** @var array<int> */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'options' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'options',
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
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 243,
            'startFilePos' => 1165,
            'endTokenPos' => 244,
            'endFilePos' => 1166,
          ),
        ),
        'docComment' => '/** @var array<int, array{name: string, values: string}> */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'variants' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'variants',
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
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 257,
            'startFilePos' => 1342,
            'endTokenPos' => 258,
            'endFilePos' => 1343,
          ),
        ),
        'docComment' => '/** @var array<int, array{sku: string, price: string, compareAtPrice: string, quantity: string, requiresShipping: bool, label: string}> */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'showSeo' => 
      array (
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'name' => 'showSeo',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 269,
            'startFilePos' => 1374,
            'endTokenPos' => 269,
            'endFilePos' => 1378,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 33,
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
          'product' => 
          array (
            'name' => 'product',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 54,
                'endLine' => 54,
                'startTokenPos' => 285,
                'startFilePos' => 1428,
                'endTokenPos' => 285,
                'endFilePos' => 1431,
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
                      'name' => 'App\\Models\\Product',
                      'isIdentifier' => false,
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 27,
            'endColumn' => 50,
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
        'startLine' => 54,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'isEditing' => 
      array (
        'name' => 'isEditing',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
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
        'docComment' => NULL,
        'startLine' => 92,
        'endLine' => 96,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'availableCollections' => 
      array (
        'name' => 'availableCollections',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Collection',
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
        'docComment' => NULL,
        'startLine' => 98,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'addOption' => 
      array (
        'name' => 'addOption',
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
        'startLine' => 109,
        'endLine' => 112,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'removeOption' => 
      array (
        'name' => 'removeOption',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
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
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 34,
            'endColumn' => 43,
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
        'startLine' => 114,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'generateVariants' => 
      array (
        'name' => 'generateVariants',
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
        'startLine' => 121,
        'endLine' => 157,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
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
        'startLine' => 159,
        'endLine' => 206,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'deleteProduct' => 
      array (
        'name' => 'deleteProduct',
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
        'startLine' => 208,
        'endLine' => 216,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'syncOptionsAndVariants' => 
      array (
        'name' => 'syncOptionsAndVariants',
        'parameters' => 
        array (
          'product' => 
          array (
            'name' => 'product',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Product',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 218,
            'endLine' => 218,
            'startColumn' => 47,
            'endColumn' => 62,
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
        'startLine' => 218,
        'endLine' => 299,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'aliasName' => NULL,
      ),
      'cartesianProduct' => 
      array (
        'name' => 'cartesianProduct',
        'parameters' => 
        array (
          'arrays' => 
          array (
            'name' => 'arrays',
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
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 41,
            'endColumn' => 53,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<int, array<int, string>>  $arrays
 * @return array<int, array<int, string>>
 */',
        'startLine' => 305,
        'endLine' => 319,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
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
          0 => 
          array (
            'name' => 'Livewire\\Attributes\\Title',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'Product\'',
                'attributes' => 
                array (
                  'startLine' => 321,
                  'endLine' => 321,
                  'startTokenPos' => 2530,
                  'startFilePos' => 11075,
                  'endTokenPos' => 2530,
                  'endFilePos' => 11083,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 321,
        'endLine' => 325,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Livewire\\Admin\\Products',
        'declaringClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'implementingClassName' => 'App\\Livewire\\Admin\\Products\\Form',
        'currentClassName' => 'App\\Livewire\\Admin\\Products\\Form',
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