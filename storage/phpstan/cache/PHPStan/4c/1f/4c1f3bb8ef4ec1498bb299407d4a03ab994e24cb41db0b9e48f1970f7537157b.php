<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/View/Factory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\View\Factory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-7bf7966582ec43436cae5e6db6cb5b5d2455fc4cab470612fd51fcb3dff39531-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\View\\Factory',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/View/Factory.php',
      ),
    ),
    'namespace' => 'Illuminate\\View',
    'name' => 'Illuminate\\View\\Factory',
    'shortName' => 'Factory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 644,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\View\\Factory',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\Traits\\Macroable',
      1 => 'Illuminate\\View\\Concerns\\ManagesComponents',
      2 => 'Illuminate\\View\\Concerns\\ManagesEvents',
      3 => 'Illuminate\\View\\Concerns\\ManagesFragments',
      4 => 'Illuminate\\View\\Concerns\\ManagesLayouts',
      5 => 'Illuminate\\View\\Concerns\\ManagesLoops',
      6 => 'Illuminate\\View\\Concerns\\ManagesStacks',
      7 => 'Illuminate\\View\\Concerns\\ManagesTranslations',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'engines' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'engines',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The engine implementation.
 *
 * @var \\Illuminate\\View\\Engines\\EngineResolver
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'finder' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'finder',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The view finder implementation.
 *
 * @var \\Illuminate\\View\\ViewFinderInterface
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'events' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'events',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The event dispatcher instance.
 *
 * @var \\Illuminate\\Contracts\\Events\\Dispatcher
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'container',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The IoC container instance.
 *
 * @var \\Illuminate\\Contracts\\Container\\Container
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'shared' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'shared',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 58,
            'startTokenPos' => 123,
            'startFilePos' => 1336,
            'endTokenPos' => 124,
            'endFilePos' => 1337,
          ),
        ),
        'docComment' => '/**
 * Data that should be available to all templates.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'extensions' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'extensions',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'blade.php\' => \'blade\', \'php\' => \'php\', \'css\' => \'file\', \'html\' => \'file\']',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 70,
            'startTokenPos' => 135,
            'startFilePos' => 1451,
            'endTokenPos' => 165,
            'endFilePos' => 1564,
          ),
        ),
        'docComment' => '/**
 * The extension to engine bindings.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'composers' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'composers',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 176,
            'startFilePos' => 1669,
            'endTokenPos' => 177,
            'endFilePos' => 1670,
          ),
        ),
        'docComment' => '/**
 * The view composer events.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'renderCount' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'renderCount',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 188,
            'startFilePos' => 1792,
            'endTokenPos' => 188,
            'endFilePos' => 1792,
          ),
        ),
        'docComment' => '/**
 * The number of active rendering operations.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'renderedOnce' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'renderedOnce',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 91,
            'endLine' => 91,
            'startTokenPos' => 199,
            'startFilePos' => 1920,
            'endTokenPos' => 200,
            'endFilePos' => 1921,
          ),
        ),
        'docComment' => '/**
 * The "once" block IDs that have been rendered.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 5,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pathEngineCache' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'pathEngineCache',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 98,
            'endLine' => 98,
            'startTokenPos' => 211,
            'startFilePos' => 2045,
            'endTokenPos' => 212,
            'endFilePos' => 2046,
          ),
        ),
        'docComment' => '/**
 * The cached array of engines for paths.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 98,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'normalizedNameCache' => 
      array (
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'name' => 'normalizedNameCache',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 105,
            'endLine' => 105,
            'startTokenPos' => 223,
            'startFilePos' => 2176,
            'endTokenPos' => 224,
            'endFilePos' => 2177,
          ),
        ),
        'docComment' => '/**
 * The cache of normalized names for views.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 105,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 40,
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
          'engines' => 
          array (
            'name' => 'engines',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\View\\Engines\\EngineResolver',
                'isIdentifier' => false,
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
            'startColumn' => 33,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'finder' => 
          array (
            'name' => 'finder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\View\\ViewFinderInterface',
                'isIdentifier' => false,
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
            'startColumn' => 58,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'events' => 
          array (
            'name' => 'events',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Events\\Dispatcher',
                'isIdentifier' => false,
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
            'startColumn' => 87,
            'endColumn' => 104,
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
 * Create a new view factory instance.
 *
 * @param  \\Illuminate\\View\\Engines\\EngineResolver  $engines
 * @param  \\Illuminate\\View\\ViewFinderInterface  $finder
 * @param  \\Illuminate\\Contracts\\Events\\Dispatcher  $events
 */',
        'startLine' => 114,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'file' => 
      array (
        'name' => 'file',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 26,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 131,
                'endLine' => 131,
                'startTokenPos' => 307,
                'startFilePos' => 2995,
                'endTokenPos' => 308,
                'endFilePos' => 2996,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 33,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'mergeData' => 
          array (
            'name' => 'mergeData',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 131,
                'endLine' => 131,
                'startTokenPos' => 315,
                'startFilePos' => 3012,
                'endTokenPos' => 316,
                'endFilePos' => 3013,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 45,
            'endColumn' => 59,
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
 * Get the evaluated view contents for the given view.
 *
 * @param  string  $path
 * @param  \\Illuminate\\Contracts\\Support\\Arrayable|array  $data
 * @param  array  $mergeData
 * @return \\Illuminate\\Contracts\\View\\View
 */',
        'startLine' => 131,
        'endLine' => 138,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'make' => 
      array (
        'name' => 'make',
        'parameters' => 
        array (
          'view' => 
          array (
            'name' => 'view',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 26,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 148,
                'endLine' => 148,
                'startTokenPos' => 394,
                'startFilePos' => 3526,
                'endTokenPos' => 395,
                'endFilePos' => 3527,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 33,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'mergeData' => 
          array (
            'name' => 'mergeData',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 148,
                'endLine' => 148,
                'startTokenPos' => 402,
                'startFilePos' => 3543,
                'endTokenPos' => 403,
                'endFilePos' => 3544,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 148,
            'endLine' => 148,
            'startColumn' => 45,
            'endColumn' => 59,
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
 * Get the evaluated view contents for the given view.
 *
 * @param  string  $view
 * @param  \\Illuminate\\Contracts\\Support\\Arrayable|array  $data
 * @param  array  $mergeData
 * @return \\Illuminate\\Contracts\\View\\View
 */',
        'startLine' => 148,
        'endLine' => 162,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'first' => 
      array (
        'name' => 'first',
        'parameters' => 
        array (
          'views' => 
          array (
            'name' => 'views',
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
            'startLine' => 174,
            'endLine' => 174,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 174,
                'endLine' => 174,
                'startTokenPos' => 514,
                'startFilePos' => 4478,
                'endTokenPos' => 515,
                'endFilePos' => 4479,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 174,
            'endLine' => 174,
            'startColumn' => 41,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'mergeData' => 
          array (
            'name' => 'mergeData',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 174,
                'endLine' => 174,
                'startTokenPos' => 522,
                'startFilePos' => 4495,
                'endTokenPos' => 523,
                'endFilePos' => 4496,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 174,
            'endLine' => 174,
            'startColumn' => 53,
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
 * Get the first view that actually exists from the given list.
 *
 * @param  array  $views
 * @param  \\Illuminate\\Contracts\\Support\\Arrayable|array  $data
 * @param  array  $mergeData
 * @return \\Illuminate\\Contracts\\View\\View
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 174,
        'endLine' => 185,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'renderWhen' => 
      array (
        'name' => 'renderWhen',
        'parameters' => 
        array (
          'condition' => 
          array (
            'name' => 'condition',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 32,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'view' => 
          array (
            'name' => 'view',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 44,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 196,
                'endLine' => 196,
                'startTokenPos' => 619,
                'startFilePos' => 5140,
                'endTokenPos' => 620,
                'endFilePos' => 5141,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 51,
            'endColumn' => 60,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'mergeData' => 
          array (
            'name' => 'mergeData',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 196,
                'endLine' => 196,
                'startTokenPos' => 627,
                'startFilePos' => 5157,
                'endTokenPos' => 628,
                'endFilePos' => 5158,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 63,
            'endColumn' => 77,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the rendered content of the view based on a given condition.
 *
 * @param  bool  $condition
 * @param  string  $view
 * @param  \\Illuminate\\Contracts\\Support\\Arrayable|array  $data
 * @param  array  $mergeData
 * @return string
 */',
        'startLine' => 196,
        'endLine' => 203,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'renderUnless' => 
      array (
        'name' => 'renderUnless',
        'parameters' => 
        array (
          'condition' => 
          array (
            'name' => 'condition',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 214,
            'endLine' => 214,
            'startColumn' => 34,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'view' => 
          array (
            'name' => 'view',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 214,
            'endLine' => 214,
            'startColumn' => 46,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 214,
                'endLine' => 214,
                'startTokenPos' => 695,
                'startFilePos' => 5673,
                'endTokenPos' => 696,
                'endFilePos' => 5674,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 214,
            'endLine' => 214,
            'startColumn' => 53,
            'endColumn' => 62,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'mergeData' => 
          array (
            'name' => 'mergeData',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 214,
                'endLine' => 214,
                'startTokenPos' => 703,
                'startFilePos' => 5690,
                'endTokenPos' => 704,
                'endFilePos' => 5691,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 214,
            'endLine' => 214,
            'startColumn' => 65,
            'endColumn' => 79,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the rendered content of the view based on the negation of a given condition.
 *
 * @param  bool  $condition
 * @param  string  $view
 * @param  \\Illuminate\\Contracts\\Support\\Arrayable|array  $data
 * @param  array  $mergeData
 * @return string
 */',
        'startLine' => 214,
        'endLine' => 217,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'renderEach' => 
      array (
        'name' => 'renderEach',
        'parameters' => 
        array (
          'view' => 
          array (
            'name' => 'view',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 32,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 39,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'iterator' => 
          array (
            'name' => 'iterator',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 46,
            'endColumn' => 54,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'empty' => 
          array (
            'name' => 'empty',
            'default' => 
            array (
              'code' => '\'raw|\'',
              'attributes' => 
              array (
                'startLine' => 228,
                'endLine' => 228,
                'startTokenPos' => 753,
                'startFilePos' => 6070,
                'endTokenPos' => 753,
                'endFilePos' => 6075,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 228,
            'endLine' => 228,
            'startColumn' => 57,
            'endColumn' => 71,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the rendered contents of a partial from a loop.
 *
 * @param  string  $view
 * @param  array  $data
 * @param  string  $iterator
 * @param  string  $empty
 * @return string
 */',
        'startLine' => 228,
        'endLine' => 253,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'normalizeName' => 
      array (
        'name' => 'normalizeName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 261,
            'endLine' => 261,
            'startColumn' => 38,
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
 * Normalize a view name.
 *
 * @param  string  $name
 * @return string
 */',
        'startLine' => 261,
        'endLine' => 264,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'parseData' => 
      array (
        'name' => 'parseData',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 272,
            'endLine' => 272,
            'startColumn' => 34,
            'endColumn' => 38,
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
 * Parse the given data into a raw array.
 *
 * @param  mixed  $data
 * @return array
 */',
        'startLine' => 272,
        'endLine' => 275,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'viewInstance' => 
      array (
        'name' => 'viewInstance',
        'parameters' => 
        array (
          'view' => 
          array (
            'name' => 'view',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 37,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 44,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 285,
            'endLine' => 285,
            'startColumn' => 51,
            'endColumn' => 55,
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
 * Create a new view instance from the given arguments.
 *
 * @param  string  $view
 * @param  string  $path
 * @param  \\Illuminate\\Contracts\\Support\\Arrayable|array  $data
 * @return \\Illuminate\\Contracts\\View\\View
 */',
        'startLine' => 285,
        'endLine' => 288,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'exists' => 
      array (
        'name' => 'exists',
        'parameters' => 
        array (
          'view' => 
          array (
            'name' => 'view',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 28,
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
 * Determine if a given view exists.
 *
 * @param  string  $view
 * @return bool
 */',
        'startLine' => 296,
        'endLine' => 305,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'getEngineFromPath' => 
      array (
        'name' => 'getEngineFromPath',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 315,
            'endLine' => 315,
            'startColumn' => 39,
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
 * Get the appropriate view engine for the given path.
 *
 * @param  string  $path
 * @return \\Illuminate\\Contracts\\View\\Engine
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 315,
        'endLine' => 328,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'getExtension' => 
      array (
        'name' => 'getExtension',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 336,
            'endLine' => 336,
            'startColumn' => 37,
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
 * Get the extension used by the view file.
 *
 * @param  string  $path
 * @return string|null
 */',
        'startLine' => 336,
        'endLine' => 343,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'share' => 
      array (
        'name' => 'share',
        'parameters' => 
        array (
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
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 27,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 352,
                'endLine' => 352,
                'startTokenPos' => 1261,
                'startFilePos' => 9517,
                'endTokenPos' => 1261,
                'endFilePos' => 9520,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 33,
            'endColumn' => 45,
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
 * Add a piece of shared data to the environment.
 *
 * @param  array|string  $key
 * @param  mixed  $value
 * @return mixed
 */',
        'startLine' => 352,
        'endLine' => 361,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'incrementRender' => 
      array (
        'name' => 'incrementRender',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Increment the rendering counter.
 *
 * @return void
 */',
        'startLine' => 368,
        'endLine' => 371,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'decrementRender' => 
      array (
        'name' => 'decrementRender',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Decrement the rendering counter.
 *
 * @return void
 */',
        'startLine' => 378,
        'endLine' => 381,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'doneRendering' => 
      array (
        'name' => 'doneRendering',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if there are no active render operations.
 *
 * @return bool
 */',
        'startLine' => 388,
        'endLine' => 391,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'hasRenderedOnce' => 
      array (
        'name' => 'hasRenderedOnce',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
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
 * Determine if the given once token has been rendered.
 *
 * @param  string  $id
 * @return bool
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
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'markAsRenderedOnce' => 
      array (
        'name' => 'markAsRenderedOnce',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
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
            'startColumn' => 40,
            'endColumn' => 49,
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
 * Mark the given once token as having been rendered.
 *
 * @param  string  $id
 * @return void
 */',
        'startLine' => 410,
        'endLine' => 413,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'addLocation' => 
      array (
        'name' => 'addLocation',
        'parameters' => 
        array (
          'location' => 
          array (
            'name' => 'location',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 421,
            'endLine' => 421,
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
 * Add a location to the array of view locations.
 *
 * @param  string  $location
 * @return void
 */',
        'startLine' => 421,
        'endLine' => 424,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'prependLocation' => 
      array (
        'name' => 'prependLocation',
        'parameters' => 
        array (
          'location' => 
          array (
            'name' => 'location',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 432,
            'endLine' => 432,
            'startColumn' => 37,
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
 * Prepend a location to the array of view locations.
 *
 * @param  string  $location
 * @return void
 */',
        'startLine' => 432,
        'endLine' => 435,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'addNamespace' => 
      array (
        'name' => 'addNamespace',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 444,
            'endLine' => 444,
            'startColumn' => 34,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'hints' => 
          array (
            'name' => 'hints',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 444,
            'endLine' => 444,
            'startColumn' => 46,
            'endColumn' => 51,
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
 * Add a new namespace to the loader.
 *
 * @param  string  $namespace
 * @param  string|array  $hints
 * @return $this
 */',
        'startLine' => 444,
        'endLine' => 449,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'prependNamespace' => 
      array (
        'name' => 'prependNamespace',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 458,
            'endLine' => 458,
            'startColumn' => 38,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'hints' => 
          array (
            'name' => 'hints',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 458,
            'endLine' => 458,
            'startColumn' => 50,
            'endColumn' => 55,
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
 * Prepend a new namespace to the loader.
 *
 * @param  string  $namespace
 * @param  string|array  $hints
 * @return $this
 */',
        'startLine' => 458,
        'endLine' => 463,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'replaceNamespace' => 
      array (
        'name' => 'replaceNamespace',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 472,
            'endLine' => 472,
            'startColumn' => 38,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'hints' => 
          array (
            'name' => 'hints',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 472,
            'endLine' => 472,
            'startColumn' => 50,
            'endColumn' => 55,
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
 * Replace the namespace hints for the given namespace.
 *
 * @param  string  $namespace
 * @param  string|array  $hints
 * @return $this
 */',
        'startLine' => 472,
        'endLine' => 477,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'addExtension' => 
      array (
        'name' => 'addExtension',
        'parameters' => 
        array (
          'extension' => 
          array (
            'name' => 'extension',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 487,
            'endLine' => 487,
            'startColumn' => 34,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'engine' => 
          array (
            'name' => 'engine',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 487,
            'endLine' => 487,
            'startColumn' => 46,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'resolver' => 
          array (
            'name' => 'resolver',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 487,
                'endLine' => 487,
                'startTokenPos' => 1627,
                'startFilePos' => 12393,
                'endTokenPos' => 1627,
                'endFilePos' => 12396,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 487,
            'endLine' => 487,
            'startColumn' => 55,
            'endColumn' => 70,
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
 * Register a valid view extension and its engine.
 *
 * @param  string  $extension
 * @param  string  $engine
 * @param  \\Closure|null  $resolver
 * @return void
 */',
        'startLine' => 487,
        'endLine' => 500,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'flushState' => 
      array (
        'name' => 'flushState',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush all of the factory state like sections and stacks.
 *
 * @return void
 */',
        'startLine' => 507,
        'endLine' => 516,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'flushStateIfDoneRendering' => 
      array (
        'name' => 'flushStateIfDoneRendering',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush all of the section contents if done rendering.
 *
 * @return void
 */',
        'startLine' => 523,
        'endLine' => 528,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'getExtensions' => 
      array (
        'name' => 'getExtensions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the extension to engine bindings.
 *
 * @return array
 */',
        'startLine' => 535,
        'endLine' => 538,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'getEngineResolver' => 
      array (
        'name' => 'getEngineResolver',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the engine resolver instance.
 *
 * @return \\Illuminate\\View\\Engines\\EngineResolver
 */',
        'startLine' => 545,
        'endLine' => 548,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'getFinder' => 
      array (
        'name' => 'getFinder',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the view finder instance.
 *
 * @return \\Illuminate\\View\\ViewFinderInterface
 */',
        'startLine' => 555,
        'endLine' => 558,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'setFinder' => 
      array (
        'name' => 'setFinder',
        'parameters' => 
        array (
          'finder' => 
          array (
            'name' => 'finder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\View\\ViewFinderInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 566,
            'endLine' => 566,
            'startColumn' => 31,
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
 * Set the view finder instance.
 *
 * @param  \\Illuminate\\View\\ViewFinderInterface  $finder
 * @return void
 */',
        'startLine' => 566,
        'endLine' => 569,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'flushFinderCache' => 
      array (
        'name' => 'flushFinderCache',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush the cache of views located by the finder.
 *
 * @return void
 */',
        'startLine' => 576,
        'endLine' => 579,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'getDispatcher' => 
      array (
        'name' => 'getDispatcher',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the event dispatcher instance.
 *
 * @return \\Illuminate\\Contracts\\Events\\Dispatcher
 */',
        'startLine' => 586,
        'endLine' => 589,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'setDispatcher' => 
      array (
        'name' => 'setDispatcher',
        'parameters' => 
        array (
          'events' => 
          array (
            'name' => 'events',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Events\\Dispatcher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 597,
            'endLine' => 597,
            'startColumn' => 35,
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
 * Set the event dispatcher instance.
 *
 * @param  \\Illuminate\\Contracts\\Events\\Dispatcher  $events
 * @return void
 */',
        'startLine' => 597,
        'endLine' => 600,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'getContainer' => 
      array (
        'name' => 'getContainer',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the IoC container instance.
 *
 * @return \\Illuminate\\Contracts\\Container\\Container
 */',
        'startLine' => 607,
        'endLine' => 610,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'setContainer' => 
      array (
        'name' => 'setContainer',
        'parameters' => 
        array (
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Container\\Container',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 618,
            'endLine' => 618,
            'startColumn' => 34,
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
 * Set the IoC container instance.
 *
 * @param  \\Illuminate\\Contracts\\Container\\Container  $container
 * @return void
 */',
        'startLine' => 618,
        'endLine' => 621,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'shared' => 
      array (
        'name' => 'shared',
        'parameters' => 
        array (
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
            'startLine' => 630,
            'endLine' => 630,
            'startColumn' => 28,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 630,
                'endLine' => 630,
                'startTokenPos' => 2033,
                'startFilePos' => 15416,
                'endTokenPos' => 2033,
                'endFilePos' => 15419,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 630,
            'endLine' => 630,
            'startColumn' => 34,
            'endColumn' => 48,
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
 * Get an item from the shared data.
 *
 * @param  string  $key
 * @param  mixed  $default
 * @return mixed
 */',
        'startLine' => 630,
        'endLine' => 633,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
        'aliasName' => NULL,
      ),
      'getShared' => 
      array (
        'name' => 'getShared',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all of the shared data for the environment.
 *
 * @return array
 */',
        'startLine' => 640,
        'endLine' => 643,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\View',
        'declaringClassName' => 'Illuminate\\View\\Factory',
        'implementingClassName' => 'Illuminate\\View\\Factory',
        'currentClassName' => 'Illuminate\\View\\Factory',
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