<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Schema/Blueprint.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Schema\Blueprint
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-5053cf9356fbfd10e55019473e5817392aecbb36f074a7b4a54b19c7426cfacb-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Schema\\Blueprint',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Schema/Blueprint.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Schema',
    'name' => 'Illuminate\\Database\\Schema\\Blueprint',
    'shortName' => 'Blueprint',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 1974,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\Traits\\Macroable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Connection',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * The database connection instance.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'grammar' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'grammar',
        'modifiers' => 2,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/**
 * The schema grammar instance.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
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
      'table' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The table the blueprint describes.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'columns' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'columns',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 108,
            'startFilePos' => 987,
            'endTokenPos' => 109,
            'endFilePos' => 988,
          ),
        ),
        'docComment' => '/**
 * The columns that should be added to the table.
 *
 * @var \\Illuminate\\Database\\Schema\\ColumnDefinition[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'commands' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'commands',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 120,
            'startFilePos' => 1136,
            'endTokenPos' => 121,
            'endFilePos' => 1137,
          ),
        ),
        'docComment' => '/**
 * The commands that should be run for the table.
 *
 * @var \\Illuminate\\Support\\Fluent[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'engine' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'engine',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The storage engine that should be used for the table.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 19,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'charset' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'charset',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The default character set that should be used for the table.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'collation' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'collation',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The collation that should be used for the table.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'temporary' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'temporary',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 153,
            'startFilePos' => 1627,
            'endTokenPos' => 153,
            'endFilePos' => 1631,
          ),
        ),
        'docComment' => '/**
 * Whether to make the table temporary.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'after' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'after',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The column to add new columns after.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'state' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'name' => 'state',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The blueprint state instance.
 *
 * @var \\Illuminate\\Database\\Schema\\BlueprintState|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
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
          'connection' => 
          array (
            'name' => 'connection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Connection',
                'isIdentifier' => false,
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
            'startColumn' => 33,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'table' => 
          array (
            'name' => 'table',
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
            'startColumn' => 57,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 193,
                'startFilePos' => 2174,
                'endTokenPos' => 193,
                'endFilePos' => 2177,
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
                      'name' => 'Closure',
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 65,
            'endColumn' => 89,
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
 * Create a new schema blueprint.
 *
 * @param  \\Illuminate\\Database\\Connection  $connection
 * @param  string  $table
 * @param  (\\Closure(self): void)|null  $callback
 */',
        'startLine' => 102,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'build' => 
      array (
        'name' => 'build',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Execute the blueprint against the database.
 *
 * @return void
 */',
        'startLine' => 118,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'toSql' => 
      array (
        'name' => 'toSql',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the raw SQL statements for the blueprint.
 *
 * @return array
 */',
        'startLine' => 130,
        'endLine' => 160,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'ensureCommandsAreValid' => 
      array (
        'name' => 'ensureCommandsAreValid',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ensure the commands on the blueprint are valid for the connection type.
 *
 * @return void
 *
 * @throws \\BadMethodCallException
 */',
        'startLine' => 169,
        'endLine' => 172,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'commandsNamed' => 
      array (
        'name' => 'commandsNamed',
        'parameters' => 
        array (
          'names' => 
          array (
            'name' => 'names',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 38,
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
 * Get all of the commands matching the given names.
 *
 * @deprecated Will be removed in a future Laravel version.
 *
 * @param  array  $names
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 182,
        'endLine' => 186,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'addImpliedCommands' => 
      array (
        'name' => 'addImpliedCommands',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add the commands that are implied by the blueprint\'s state.
 *
 * @return void
 */',
        'startLine' => 193,
        'endLine' => 208,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'addFluentIndexes' => 
      array (
        'name' => 'addFluentIndexes',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add the index commands fluently specified on columns.
 *
 * @return void
 */',
        'startLine' => 215,
        'endLine' => 265,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'addFluentCommands' => 
      array (
        'name' => 'addFluentCommands',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add the fluent commands specified on any columns.
 *
 * @return void
 */',
        'startLine' => 272,
        'endLine' => 279,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'addAlterCommands' => 
      array (
        'name' => 'addAlterCommands',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add the alter commands if whenever needed.
 *
 * @return void
 */',
        'startLine' => 286,
        'endLine' => 319,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'creating' => 
      array (
        'name' => 'creating',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the blueprint has a create command.
 *
 * @return bool
 */',
        'startLine' => 326,
        'endLine' => 330,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the table needs to be created.
 *
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 337,
        'endLine' => 340,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'engine' => 
      array (
        'name' => 'engine',
        'parameters' => 
        array (
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
            'startLine' => 348,
            'endLine' => 348,
            'startColumn' => 28,
            'endColumn' => 34,
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
 * Specify the storage engine that should be used for the table.
 *
 * @param  string  $engine
 * @return void
 */',
        'startLine' => 348,
        'endLine' => 351,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'innoDb' => 
      array (
        'name' => 'innoDb',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Specify that the InnoDB storage engine should be used for the table (MySQL only).
 *
 * @return void
 */',
        'startLine' => 358,
        'endLine' => 361,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'charset' => 
      array (
        'name' => 'charset',
        'parameters' => 
        array (
          'charset' => 
          array (
            'name' => 'charset',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 369,
            'endLine' => 369,
            'startColumn' => 29,
            'endColumn' => 36,
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
 * Specify the character set that should be used for the table.
 *
 * @param  string  $charset
 * @return void
 */',
        'startLine' => 369,
        'endLine' => 372,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'collation' => 
      array (
        'name' => 'collation',
        'parameters' => 
        array (
          'collation' => 
          array (
            'name' => 'collation',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 380,
            'endLine' => 380,
            'startColumn' => 31,
            'endColumn' => 40,
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
 * Specify the collation that should be used for the table.
 *
 * @param  string  $collation
 * @return void
 */',
        'startLine' => 380,
        'endLine' => 383,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'temporary' => 
      array (
        'name' => 'temporary',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the table needs to be temporary.
 *
 * @return void
 */',
        'startLine' => 390,
        'endLine' => 393,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'drop' => 
      array (
        'name' => 'drop',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the table should be dropped.
 *
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 400,
        'endLine' => 403,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropIfExists' => 
      array (
        'name' => 'dropIfExists',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the table should be dropped if it exists.
 *
 * @return \\Illuminate\\Support\\Fluent
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
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropColumn' => 
      array (
        'name' => 'dropColumn',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
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
 * Indicate that the given columns should be dropped.
 *
 * @param  mixed  $columns
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 421,
        'endLine' => 426,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'renameColumn' => 
      array (
        'name' => 'renameColumn',
        'parameters' => 
        array (
          'from' => 
          array (
            'name' => 'from',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 435,
            'endLine' => 435,
            'startColumn' => 34,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'to' => 
          array (
            'name' => 'to',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 435,
            'endLine' => 435,
            'startColumn' => 41,
            'endColumn' => 43,
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
 * Indicate that the given columns should be renamed.
 *
 * @param  string  $from
 * @param  string  $to
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 435,
        'endLine' => 438,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropPrimary' => 
      array (
        'name' => 'dropPrimary',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 446,
                'endLine' => 446,
                'startTokenPos' => 1646,
                'startFilePos' => 11922,
                'endTokenPos' => 1646,
                'endFilePos' => 11925,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 446,
            'endLine' => 446,
            'startColumn' => 33,
            'endColumn' => 45,
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
 * Indicate that the given primary key should be dropped.
 *
 * @param  string|array|null  $index
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 446,
        'endLine' => 449,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropUnique' => 
      array (
        'name' => 'dropUnique',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 457,
            'endLine' => 457,
            'startColumn' => 32,
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
 * Indicate that the given unique key should be dropped.
 *
 * @param  string|array  $index
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 457,
        'endLine' => 460,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropIndex' => 
      array (
        'name' => 'dropIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 468,
            'endLine' => 468,
            'startColumn' => 31,
            'endColumn' => 36,
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
 * Indicate that the given index should be dropped.
 *
 * @param  string|array  $index
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 468,
        'endLine' => 471,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropFullText' => 
      array (
        'name' => 'dropFullText',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 479,
            'endLine' => 479,
            'startColumn' => 34,
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
 * Indicate that the given fulltext index should be dropped.
 *
 * @param  string|array  $index
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 479,
        'endLine' => 482,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropSpatialIndex' => 
      array (
        'name' => 'dropSpatialIndex',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 490,
            'endLine' => 490,
            'startColumn' => 38,
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
 * Indicate that the given spatial index should be dropped.
 *
 * @param  string|array  $index
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 490,
        'endLine' => 493,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropForeign' => 
      array (
        'name' => 'dropForeign',
        'parameters' => 
        array (
          'index' => 
          array (
            'name' => 'index',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 501,
            'endLine' => 501,
            'startColumn' => 33,
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
 * Indicate that the given foreign key should be dropped.
 *
 * @param  string|array  $index
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 501,
        'endLine' => 504,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropConstrainedForeignId' => 
      array (
        'name' => 'dropConstrainedForeignId',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 512,
            'endLine' => 512,
            'startColumn' => 46,
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
 * Indicate that the given column and foreign key should be dropped.
 *
 * @param  string  $column
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 512,
        'endLine' => 517,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropForeignIdFor' => 
      array (
        'name' => 'dropForeignIdFor',
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
            'startLine' => 526,
            'endLine' => 526,
            'startColumn' => 38,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 526,
                'endLine' => 526,
                'startTokenPos' => 1874,
                'startFilePos' => 14075,
                'endTokenPos' => 1874,
                'endFilePos' => 14078,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 526,
            'endLine' => 526,
            'startColumn' => 46,
            'endColumn' => 59,
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
 * Indicate that the given foreign key should be dropped.
 *
 * @param  \\Illuminate\\Database\\Eloquent\\Model|string  $model
 * @param  string|null  $column
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 526,
        'endLine' => 533,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropConstrainedForeignIdFor' => 
      array (
        'name' => 'dropConstrainedForeignIdFor',
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
            'startLine' => 542,
            'endLine' => 542,
            'startColumn' => 49,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 542,
                'endLine' => 542,
                'startTokenPos' => 1937,
                'startFilePos' => 14536,
                'endTokenPos' => 1937,
                'endFilePos' => 14539,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 542,
            'endLine' => 542,
            'startColumn' => 57,
            'endColumn' => 70,
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
 * Indicate that the given foreign key should be dropped.
 *
 * @param  \\Illuminate\\Database\\Eloquent\\Model|string  $model
 * @param  string|null  $column
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 542,
        'endLine' => 549,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'renameIndex' => 
      array (
        'name' => 'renameIndex',
        'parameters' => 
        array (
          'from' => 
          array (
            'name' => 'from',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 558,
            'endLine' => 558,
            'startColumn' => 33,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'to' => 
          array (
            'name' => 'to',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 558,
            'endLine' => 558,
            'startColumn' => 40,
            'endColumn' => 42,
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
 * Indicate that the given indexes should be renamed.
 *
 * @param  string  $from
 * @param  string  $to
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 558,
        'endLine' => 561,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropTimestamps' => 
      array (
        'name' => 'dropTimestamps',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the timestamp columns should be dropped.
 *
 * @return void
 */',
        'startLine' => 568,
        'endLine' => 571,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropTimestampsTz' => 
      array (
        'name' => 'dropTimestampsTz',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the timestamp columns should be dropped.
 *
 * @return void
 */',
        'startLine' => 578,
        'endLine' => 581,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropSoftDeletes' => 
      array (
        'name' => 'dropSoftDeletes',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'deleted_at\'',
              'attributes' => 
              array (
                'startLine' => 589,
                'endLine' => 589,
                'startTokenPos' => 2080,
                'startFilePos' => 15607,
                'endTokenPos' => 2080,
                'endFilePos' => 15618,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 589,
            'endLine' => 589,
            'startColumn' => 37,
            'endColumn' => 58,
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
 * Indicate that the soft delete column should be dropped.
 *
 * @param  string  $column
 * @return void
 */',
        'startLine' => 589,
        'endLine' => 592,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropSoftDeletesTz' => 
      array (
        'name' => 'dropSoftDeletesTz',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'deleted_at\'',
              'attributes' => 
              array (
                'startLine' => 600,
                'endLine' => 600,
                'startTokenPos' => 2107,
                'startFilePos' => 15855,
                'endTokenPos' => 2107,
                'endFilePos' => 15866,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 600,
            'endLine' => 600,
            'startColumn' => 39,
            'endColumn' => 60,
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
 * Indicate that the soft delete column should be dropped.
 *
 * @param  string  $column
 * @return void
 */',
        'startLine' => 600,
        'endLine' => 603,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropRememberToken' => 
      array (
        'name' => 'dropRememberToken',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the remember token column should be dropped.
 *
 * @return void
 */',
        'startLine' => 610,
        'endLine' => 613,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropMorphs' => 
      array (
        'name' => 'dropMorphs',
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
            'startLine' => 622,
            'endLine' => 622,
            'startColumn' => 32,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 622,
                'endLine' => 622,
                'startTokenPos' => 2159,
                'startFilePos' => 16356,
                'endTokenPos' => 2159,
                'endFilePos' => 16359,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 622,
            'endLine' => 622,
            'startColumn' => 39,
            'endColumn' => 55,
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
 * Indicate that the polymorphic columns should be dropped.
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @return void
 */',
        'startLine' => 622,
        'endLine' => 627,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'rename' => 
      array (
        'name' => 'rename',
        'parameters' => 
        array (
          'to' => 
          array (
            'name' => 'to',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 635,
            'endLine' => 635,
            'startColumn' => 28,
            'endColumn' => 30,
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
 * Rename the table to a given name.
 *
 * @param  string  $to
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 635,
        'endLine' => 638,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'primary' => 
      array (
        'name' => 'primary',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 648,
            'endLine' => 648,
            'startColumn' => 29,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 648,
                'endLine' => 648,
                'startTokenPos' => 2269,
                'startFilePos' => 17063,
                'endTokenPos' => 2269,
                'endFilePos' => 17066,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 648,
            'endLine' => 648,
            'startColumn' => 39,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'algorithm' => 
          array (
            'name' => 'algorithm',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 648,
                'endLine' => 648,
                'startTokenPos' => 2276,
                'startFilePos' => 17082,
                'endTokenPos' => 2276,
                'endFilePos' => 17085,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 648,
            'endLine' => 648,
            'startColumn' => 53,
            'endColumn' => 69,
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
 * Specify the primary key(s) for the table.
 *
 * @param  string|array  $columns
 * @param  string|null  $name
 * @param  string|null  $algorithm
 * @return \\Illuminate\\Database\\Schema\\IndexDefinition
 */',
        'startLine' => 648,
        'endLine' => 651,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'unique' => 
      array (
        'name' => 'unique',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 661,
            'endLine' => 661,
            'startColumn' => 28,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 661,
                'endLine' => 661,
                'startTokenPos' => 2317,
                'startFilePos' => 17460,
                'endTokenPos' => 2317,
                'endFilePos' => 17463,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 661,
            'endLine' => 661,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'algorithm' => 
          array (
            'name' => 'algorithm',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 661,
                'endLine' => 661,
                'startTokenPos' => 2324,
                'startFilePos' => 17479,
                'endTokenPos' => 2324,
                'endFilePos' => 17482,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 661,
            'endLine' => 661,
            'startColumn' => 52,
            'endColumn' => 68,
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
 * Specify a unique index for the table.
 *
 * @param  string|array  $columns
 * @param  string|null  $name
 * @param  string|null  $algorithm
 * @return \\Illuminate\\Database\\Schema\\IndexDefinition
 */',
        'startLine' => 661,
        'endLine' => 664,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'index' => 
      array (
        'name' => 'index',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 674,
            'endLine' => 674,
            'startColumn' => 27,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 674,
                'endLine' => 674,
                'startTokenPos' => 2365,
                'startFilePos' => 17849,
                'endTokenPos' => 2365,
                'endFilePos' => 17852,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 674,
            'endLine' => 674,
            'startColumn' => 37,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'algorithm' => 
          array (
            'name' => 'algorithm',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 674,
                'endLine' => 674,
                'startTokenPos' => 2372,
                'startFilePos' => 17868,
                'endTokenPos' => 2372,
                'endFilePos' => 17871,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 674,
            'endLine' => 674,
            'startColumn' => 51,
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
 * Specify an index for the table.
 *
 * @param  string|array  $columns
 * @param  string|null  $name
 * @param  string|null  $algorithm
 * @return \\Illuminate\\Database\\Schema\\IndexDefinition
 */',
        'startLine' => 674,
        'endLine' => 677,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'fullText' => 
      array (
        'name' => 'fullText',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 687,
            'endLine' => 687,
            'startColumn' => 30,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 687,
                'endLine' => 687,
                'startTokenPos' => 2413,
                'startFilePos' => 18248,
                'endTokenPos' => 2413,
                'endFilePos' => 18251,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 687,
            'endLine' => 687,
            'startColumn' => 40,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'algorithm' => 
          array (
            'name' => 'algorithm',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 687,
                'endLine' => 687,
                'startTokenPos' => 2420,
                'startFilePos' => 18267,
                'endTokenPos' => 2420,
                'endFilePos' => 18270,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 687,
            'endLine' => 687,
            'startColumn' => 54,
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
 * Specify a fulltext index for the table.
 *
 * @param  string|array  $columns
 * @param  string|null  $name
 * @param  string|null  $algorithm
 * @return \\Illuminate\\Database\\Schema\\IndexDefinition
 */',
        'startLine' => 687,
        'endLine' => 690,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'spatialIndex' => 
      array (
        'name' => 'spatialIndex',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 700,
            'endLine' => 700,
            'startColumn' => 34,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 700,
                'endLine' => 700,
                'startTokenPos' => 2461,
                'startFilePos' => 18657,
                'endTokenPos' => 2461,
                'endFilePos' => 18660,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 700,
            'endLine' => 700,
            'startColumn' => 44,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'operatorClass' => 
          array (
            'name' => 'operatorClass',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 700,
                'endLine' => 700,
                'startTokenPos' => 2468,
                'startFilePos' => 18680,
                'endTokenPos' => 2468,
                'endFilePos' => 18683,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 700,
            'endLine' => 700,
            'startColumn' => 58,
            'endColumn' => 78,
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
 * Specify a spatial index for the table.
 *
 * @param  string|array  $columns
 * @param  string|null  $name
 * @param  string|null  $operatorClass
 * @return \\Illuminate\\Database\\Schema\\IndexDefinition
 */',
        'startLine' => 700,
        'endLine' => 703,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'vectorIndex' => 
      array (
        'name' => 'vectorIndex',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 712,
            'endLine' => 712,
            'startColumn' => 33,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 712,
                'endLine' => 712,
                'startTokenPos' => 2512,
                'startFilePos' => 19031,
                'endTokenPos' => 2512,
                'endFilePos' => 19034,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 712,
            'endLine' => 712,
            'startColumn' => 42,
            'endColumn' => 53,
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
 * Specify a vector index for the table.
 *
 * @param  string  $column
 * @param  string|null  $name
 * @return \\Illuminate\\Database\\Schema\\IndexDefinition
 */',
        'startLine' => 712,
        'endLine' => 715,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'rawIndex' => 
      array (
        'name' => 'rawIndex',
        'parameters' => 
        array (
          'expression' => 
          array (
            'name' => 'expression',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 724,
            'endLine' => 724,
            'startColumn' => 30,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 724,
            'endLine' => 724,
            'startColumn' => 43,
            'endColumn' => 47,
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
 * Specify a raw index for the table.
 *
 * @param  string  $expression
 * @param  string  $name
 * @return \\Illuminate\\Database\\Schema\\IndexDefinition
 */',
        'startLine' => 724,
        'endLine' => 727,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'foreign' => 
      array (
        'name' => 'foreign',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 736,
            'endLine' => 736,
            'startColumn' => 29,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 736,
                'endLine' => 736,
                'startTokenPos' => 2594,
                'startFilePos' => 19712,
                'endTokenPos' => 2594,
                'endFilePos' => 19715,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 736,
            'endLine' => 736,
            'startColumn' => 39,
            'endColumn' => 50,
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
 * Specify a foreign key for the table.
 *
 * @param  string|array  $columns
 * @param  string|null  $name
 * @return \\Illuminate\\Database\\Schema\\ForeignKeyDefinition
 */',
        'startLine' => 736,
        'endLine' => 745,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'id' => 
      array (
        'name' => 'id',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'id\'',
              'attributes' => 
              array (
                'startLine' => 753,
                'endLine' => 753,
                'startTokenPos' => 2668,
                'startFilePos' => 20215,
                'endTokenPos' => 2668,
                'endFilePos' => 20218,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 753,
            'endLine' => 753,
            'startColumn' => 24,
            'endColumn' => 37,
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
 * Create a new auto-incrementing big integer column on the table (8-byte, 0 to 18,446,744,073,709,551,615).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 753,
        'endLine' => 756,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'increments' => 
      array (
        'name' => 'increments',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 764,
            'endLine' => 764,
            'startColumn' => 32,
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
 * Create a new auto-incrementing integer column on the table (4-byte, 0 to 4,294,967,295).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 764,
        'endLine' => 767,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'integerIncrements' => 
      array (
        'name' => 'integerIncrements',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 775,
            'endLine' => 775,
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
 * Create a new auto-incrementing integer column on the table (4-byte, 0 to 4,294,967,295).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 775,
        'endLine' => 778,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'tinyIncrements' => 
      array (
        'name' => 'tinyIncrements',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 786,
            'endLine' => 786,
            'startColumn' => 36,
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
 * Create a new auto-incrementing tiny integer column on the table (1-byte, 0 to 255).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 786,
        'endLine' => 789,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'smallIncrements' => 
      array (
        'name' => 'smallIncrements',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 797,
            'endLine' => 797,
            'startColumn' => 37,
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
 * Create a new auto-incrementing small integer column on the table (2-byte, 0 to 65,535).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 797,
        'endLine' => 800,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'mediumIncrements' => 
      array (
        'name' => 'mediumIncrements',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 38,
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
 * Create a new auto-incrementing medium integer column on the table (3-byte, 0 to 16,777,215).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 808,
        'endLine' => 811,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'bigIncrements' => 
      array (
        'name' => 'bigIncrements',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 819,
            'endLine' => 819,
            'startColumn' => 35,
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
 * Create a new auto-incrementing big integer column on the table (8-byte, 0 to 18,446,744,073,709,551,615).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 819,
        'endLine' => 822,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'char' => 
      array (
        'name' => 'char',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 831,
            'endLine' => 831,
            'startColumn' => 26,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 831,
                'endLine' => 831,
                'startTokenPos' => 2868,
                'startFilePos' => 22477,
                'endTokenPos' => 2868,
                'endFilePos' => 22480,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 831,
            'endLine' => 831,
            'startColumn' => 35,
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
 * Create a new char column on the table.
 *
 * @param  string  $column
 * @param  int|null  $length
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 831,
        'endLine' => 836,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'string' => 
      array (
        'name' => 'string',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 845,
            'endLine' => 845,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 845,
                'endLine' => 845,
                'startTokenPos' => 2931,
                'startFilePos' => 22887,
                'endTokenPos' => 2931,
                'endFilePos' => 22890,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 845,
            'endLine' => 845,
            'startColumn' => 37,
            'endColumn' => 50,
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
 * Create a new string column on the table.
 *
 * @param  string  $column
 * @param  int|null  $length
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 845,
        'endLine' => 850,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'tinyText' => 
      array (
        'name' => 'tinyText',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 858,
            'endLine' => 858,
            'startColumn' => 30,
            'endColumn' => 36,
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
 * Create a new tiny text column on the table (up to 255 characters).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 858,
        'endLine' => 861,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'text' => 
      array (
        'name' => 'text',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 869,
            'endLine' => 869,
            'startColumn' => 26,
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
 * Create a new text column on the table (up to 65,535 characters / ~64 KB).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 869,
        'endLine' => 872,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'mediumText' => 
      array (
        'name' => 'mediumText',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 880,
            'endLine' => 880,
            'startColumn' => 32,
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
 * Create a new medium text column on the table (up to 16,777,215 characters / ~16 MB).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 880,
        'endLine' => 883,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'longText' => 
      array (
        'name' => 'longText',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 891,
            'endLine' => 891,
            'startColumn' => 30,
            'endColumn' => 36,
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
 * Create a new long text column on the table (up to 4,294,967,295 characters / ~4 GB).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 891,
        'endLine' => 894,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'integer' => 
      array (
        'name' => 'integer',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 905,
            'endLine' => 905,
            'startColumn' => 29,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 905,
                'endLine' => 905,
                'startTokenPos' => 3098,
                'startFilePos' => 24631,
                'endTokenPos' => 3098,
                'endFilePos' => 24635,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 905,
            'endLine' => 905,
            'startColumn' => 38,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'unsigned' => 
          array (
            'name' => 'unsigned',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 905,
                'endLine' => 905,
                'startTokenPos' => 3105,
                'startFilePos' => 24650,
                'endTokenPos' => 3105,
                'endFilePos' => 24654,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 905,
            'endLine' => 905,
            'startColumn' => 62,
            'endColumn' => 78,
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
 * Create a new integer (4-byte) column on the table.
 * Range: -2,147,483,648 to 2,147,483,647 (signed) or 0 to 4,294,967,295 (unsigned).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @param  bool  $unsigned
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 905,
        'endLine' => 908,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'tinyInteger' => 
      array (
        'name' => 'tinyInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 919,
            'endLine' => 919,
            'startColumn' => 33,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 919,
                'endLine' => 919,
                'startTokenPos' => 3149,
                'startFilePos' => 25122,
                'endTokenPos' => 3149,
                'endFilePos' => 25126,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 919,
            'endLine' => 919,
            'startColumn' => 42,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'unsigned' => 
          array (
            'name' => 'unsigned',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 919,
                'endLine' => 919,
                'startTokenPos' => 3156,
                'startFilePos' => 25141,
                'endTokenPos' => 3156,
                'endFilePos' => 25145,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 919,
            'endLine' => 919,
            'startColumn' => 66,
            'endColumn' => 82,
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
 * Create a new tiny integer (1-byte) column on the table.
 * Range: -128 to 127 (signed) or 0 to 255 (unsigned).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @param  bool  $unsigned
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 919,
        'endLine' => 922,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'smallInteger' => 
      array (
        'name' => 'smallInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 933,
            'endLine' => 933,
            'startColumn' => 34,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 933,
                'endLine' => 933,
                'startTokenPos' => 3200,
                'startFilePos' => 25628,
                'endTokenPos' => 3200,
                'endFilePos' => 25632,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 933,
            'endLine' => 933,
            'startColumn' => 43,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'unsigned' => 
          array (
            'name' => 'unsigned',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 933,
                'endLine' => 933,
                'startTokenPos' => 3207,
                'startFilePos' => 25647,
                'endTokenPos' => 3207,
                'endFilePos' => 25651,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 933,
            'endLine' => 933,
            'startColumn' => 67,
            'endColumn' => 83,
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
 * Create a new small integer (2-byte) column on the table.
 * Range: -32,768 to 32,767 (signed) or 0 to 65,535 (unsigned).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @param  bool  $unsigned
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 933,
        'endLine' => 936,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'mediumInteger' => 
      array (
        'name' => 'mediumInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 947,
            'endLine' => 947,
            'startColumn' => 35,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 947,
                'endLine' => 947,
                'startTokenPos' => 3251,
                'startFilePos' => 26147,
                'endTokenPos' => 3251,
                'endFilePos' => 26151,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 947,
            'endLine' => 947,
            'startColumn' => 44,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'unsigned' => 
          array (
            'name' => 'unsigned',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 947,
                'endLine' => 947,
                'startTokenPos' => 3258,
                'startFilePos' => 26166,
                'endTokenPos' => 3258,
                'endFilePos' => 26170,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 947,
            'endLine' => 947,
            'startColumn' => 68,
            'endColumn' => 84,
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
 * Create a new medium integer (3-byte) column on the table.
 * Range: -8,388,608 to 8,388,607 (signed) or 0 to 16,777,215 (unsigned).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @param  bool  $unsigned
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 947,
        'endLine' => 950,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'bigInteger' => 
      array (
        'name' => 'bigInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 961,
            'endLine' => 961,
            'startColumn' => 32,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 961,
                'endLine' => 961,
                'startTokenPos' => 3302,
                'startFilePos' => 26709,
                'endTokenPos' => 3302,
                'endFilePos' => 26713,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 961,
            'endLine' => 961,
            'startColumn' => 41,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'unsigned' => 
          array (
            'name' => 'unsigned',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 961,
                'endLine' => 961,
                'startTokenPos' => 3309,
                'startFilePos' => 26728,
                'endTokenPos' => 3309,
                'endFilePos' => 26732,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 961,
            'endLine' => 961,
            'startColumn' => 65,
            'endColumn' => 81,
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
 * Create a new big integer (8-byte) column on the table.
 * Range: -9,223,372,036,854,775,808 to 9,223,372,036,854,775,807 (signed) or 0 to 18,446,744,073,709,551,615 (unsigned).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @param  bool  $unsigned
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 961,
        'endLine' => 964,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'unsignedInteger' => 
      array (
        'name' => 'unsignedInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 973,
            'endLine' => 973,
            'startColumn' => 37,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 973,
                'endLine' => 973,
                'startTokenPos' => 3353,
                'startFilePos' => 27141,
                'endTokenPos' => 3353,
                'endFilePos' => 27145,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 973,
            'endLine' => 973,
            'startColumn' => 46,
            'endColumn' => 67,
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
 * Create a new unsigned integer column on the table (4-byte, 0 to 4,294,967,295).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 973,
        'endLine' => 976,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'unsignedTinyInteger' => 
      array (
        'name' => 'unsignedTinyInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 985,
            'endLine' => 985,
            'startColumn' => 41,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 985,
                'endLine' => 985,
                'startTokenPos' => 3391,
                'startFilePos' => 27521,
                'endTokenPos' => 3391,
                'endFilePos' => 27525,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 985,
            'endLine' => 985,
            'startColumn' => 50,
            'endColumn' => 71,
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
 * Create a new unsigned tiny integer column on the table (1-byte, 0 to 255).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 985,
        'endLine' => 988,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'unsignedSmallInteger' => 
      array (
        'name' => 'unsignedSmallInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 997,
            'endLine' => 997,
            'startColumn' => 42,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 997,
                'endLine' => 997,
                'startTokenPos' => 3429,
                'startFilePos' => 27910,
                'endTokenPos' => 3429,
                'endFilePos' => 27914,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 997,
            'endLine' => 997,
            'startColumn' => 51,
            'endColumn' => 72,
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
 * Create a new unsigned small integer column on the table (2-byte, 0 to 65,535).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 997,
        'endLine' => 1000,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'unsignedMediumInteger' => 
      array (
        'name' => 'unsignedMediumInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1009,
            'endLine' => 1009,
            'startColumn' => 43,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 1009,
                'endLine' => 1009,
                'startTokenPos' => 3467,
                'startFilePos' => 28306,
                'endTokenPos' => 3467,
                'endFilePos' => 28310,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1009,
            'endLine' => 1009,
            'startColumn' => 52,
            'endColumn' => 73,
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
 * Create a new unsigned medium integer column on the table (3-byte, 0 to 16,777,215).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1009,
        'endLine' => 1012,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'unsignedBigInteger' => 
      array (
        'name' => 'unsignedBigInteger',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1021,
            'endLine' => 1021,
            'startColumn' => 40,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'autoIncrement' => 
          array (
            'name' => 'autoIncrement',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 1021,
                'endLine' => 1021,
                'startTokenPos' => 3505,
                'startFilePos' => 28713,
                'endTokenPos' => 3505,
                'endFilePos' => 28717,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1021,
            'endLine' => 1021,
            'startColumn' => 49,
            'endColumn' => 70,
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
 * Create a new unsigned big integer column on the table (8-byte, 0 to 18,446,744,073,709,551,615).
 *
 * @param  string  $column
 * @param  bool  $autoIncrement
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1021,
        'endLine' => 1024,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'foreignId' => 
      array (
        'name' => 'foreignId',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1032,
            'endLine' => 1032,
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
 * Create a new unsigned big integer column on the table (8-byte, 0 to 18,446,744,073,709,551,615).
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ForeignIdColumnDefinition
 */',
        'startLine' => 1032,
        'endLine' => 1040,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'foreignIdFor' => 
      array (
        'name' => 'foreignIdFor',
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
            'startLine' => 1049,
            'endLine' => 1049,
            'startColumn' => 34,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1049,
                'endLine' => 1049,
                'startTokenPos' => 3606,
                'startFilePos' => 29608,
                'endTokenPos' => 3606,
                'endFilePos' => 29611,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1049,
            'endLine' => 1049,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * Create a foreign ID column for the given model.
 *
 * @param  \\Illuminate\\Database\\Eloquent\\Model|string  $model
 * @param  string|null  $column
 * @return \\Illuminate\\Database\\Schema\\ForeignIdColumnDefinition
 */',
        'startLine' => 1049,
        'endLine' => 1074,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'float' => 
      array (
        'name' => 'float',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1083,
            'endLine' => 1083,
            'startColumn' => 27,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => '53',
              'attributes' => 
              array (
                'startLine' => 1083,
                'endLine' => 1083,
                'startTokenPos' => 3808,
                'startFilePos' => 30634,
                'endTokenPos' => 3808,
                'endFilePos' => 30635,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1083,
            'endLine' => 1083,
            'startColumn' => 36,
            'endColumn' => 50,
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
 * Create a new float column on the table.
 *
 * @param  string  $column
 * @param  int  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1083,
        'endLine' => 1086,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'double' => 
      array (
        'name' => 'double',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1094,
            'endLine' => 1094,
            'startColumn' => 28,
            'endColumn' => 34,
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
 * Create a new double column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1094,
        'endLine' => 1097,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'decimal' => 
      array (
        'name' => 'decimal',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1107,
            'endLine' => 1107,
            'startColumn' => 29,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'total' => 
          array (
            'name' => 'total',
            'default' => 
            array (
              'code' => '8',
              'attributes' => 
              array (
                'startLine' => 1107,
                'endLine' => 1107,
                'startTokenPos' => 3877,
                'startFilePos' => 31251,
                'endTokenPos' => 3877,
                'endFilePos' => 31251,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1107,
            'endLine' => 1107,
            'startColumn' => 38,
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'places' => 
          array (
            'name' => 'places',
            'default' => 
            array (
              'code' => '2',
              'attributes' => 
              array (
                'startLine' => 1107,
                'endLine' => 1107,
                'startTokenPos' => 3884,
                'startFilePos' => 31264,
                'endTokenPos' => 3884,
                'endFilePos' => 31264,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1107,
            'endLine' => 1107,
            'startColumn' => 50,
            'endColumn' => 60,
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
 * Create a new decimal column on the table.
 *
 * @param  string  $column
 * @param  int  $total
 * @param  int  $places
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1107,
        'endLine' => 1110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'boolean' => 
      array (
        'name' => 'boolean',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1118,
            'endLine' => 1118,
            'startColumn' => 29,
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
 * Create a new boolean column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1118,
        'endLine' => 1121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'enum' => 
      array (
        'name' => 'enum',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1130,
            'endLine' => 1130,
            'startColumn' => 26,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'allowed' => 
          array (
            'name' => 'allowed',
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
            'startLine' => 1130,
            'endLine' => 1130,
            'startColumn' => 35,
            'endColumn' => 48,
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
 * Create a new enum column on the table.
 *
 * @param  string  $column
 * @param  array  $allowed
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1130,
        'endLine' => 1135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'set' => 
      array (
        'name' => 'set',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1144,
            'endLine' => 1144,
            'startColumn' => 25,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'allowed' => 
          array (
            'name' => 'allowed',
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
            'startLine' => 1144,
            'endLine' => 1144,
            'startColumn' => 34,
            'endColumn' => 47,
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
 * Create a new set column on the table.
 *
 * @param  string  $column
 * @param  array  $allowed
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1144,
        'endLine' => 1147,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'json' => 
      array (
        'name' => 'json',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1155,
            'endLine' => 1155,
            'startColumn' => 26,
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
 * Create a new json column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1155,
        'endLine' => 1158,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'jsonb' => 
      array (
        'name' => 'jsonb',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1166,
            'endLine' => 1166,
            'startColumn' => 27,
            'endColumn' => 33,
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
 * Create a new jsonb column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1166,
        'endLine' => 1169,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'date' => 
      array (
        'name' => 'date',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1177,
            'endLine' => 1177,
            'startColumn' => 26,
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
 * Create a new date column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1177,
        'endLine' => 1180,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dateTime' => 
      array (
        'name' => 'dateTime',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1189,
            'endLine' => 1189,
            'startColumn' => 30,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1189,
                'endLine' => 1189,
                'startTokenPos' => 4142,
                'startFilePos' => 33374,
                'endTokenPos' => 4142,
                'endFilePos' => 33377,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1189,
            'endLine' => 1189,
            'startColumn' => 39,
            'endColumn' => 55,
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
 * Create a new date-time column on the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1189,
        'endLine' => 1194,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dateTimeTz' => 
      array (
        'name' => 'dateTimeTz',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1203,
            'endLine' => 1203,
            'startColumn' => 32,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1203,
                'endLine' => 1203,
                'startTokenPos' => 4194,
                'startFilePos' => 33795,
                'endTokenPos' => 4194,
                'endFilePos' => 33798,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1203,
            'endLine' => 1203,
            'startColumn' => 41,
            'endColumn' => 57,
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
 * Create a new date-time column (with time zone) on the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1203,
        'endLine' => 1208,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'time' => 
      array (
        'name' => 'time',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1217,
            'endLine' => 1217,
            'startColumn' => 26,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1217,
                'endLine' => 1217,
                'startTokenPos' => 4246,
                'startFilePos' => 34190,
                'endTokenPos' => 4246,
                'endFilePos' => 34193,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1217,
            'endLine' => 1217,
            'startColumn' => 35,
            'endColumn' => 51,
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
 * Create a new time column on the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1217,
        'endLine' => 1222,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'timeTz' => 
      array (
        'name' => 'timeTz',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1231,
            'endLine' => 1231,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1231,
                'endLine' => 1231,
                'startTokenPos' => 4298,
                'startFilePos' => 34598,
                'endTokenPos' => 4298,
                'endFilePos' => 34601,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1231,
            'endLine' => 1231,
            'startColumn' => 37,
            'endColumn' => 53,
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
 * Create a new time column (with time zone) on the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1231,
        'endLine' => 1236,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'timestamp' => 
      array (
        'name' => 'timestamp',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1245,
            'endLine' => 1245,
            'startColumn' => 31,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1245,
                'endLine' => 1245,
                'startTokenPos' => 4350,
                'startFilePos' => 34999,
                'endTokenPos' => 4350,
                'endFilePos' => 35002,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1245,
            'endLine' => 1245,
            'startColumn' => 40,
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
 * Create a new timestamp column on the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1245,
        'endLine' => 1250,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'timestampTz' => 
      array (
        'name' => 'timestampTz',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1259,
            'endLine' => 1259,
            'startColumn' => 33,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1259,
                'endLine' => 1259,
                'startTokenPos' => 4402,
                'startFilePos' => 35422,
                'endTokenPos' => 4402,
                'endFilePos' => 35425,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1259,
            'endLine' => 1259,
            'startColumn' => 42,
            'endColumn' => 58,
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
 * Create a new timestamp (with time zone) column on the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1259,
        'endLine' => 1264,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'timestamps' => 
      array (
        'name' => 'timestamps',
        'parameters' => 
        array (
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1272,
                'endLine' => 1272,
                'startTokenPos' => 4451,
                'startFilePos' => 35840,
                'endTokenPos' => 4451,
                'endFilePos' => 35843,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1272,
            'endLine' => 1272,
            'startColumn' => 32,
            'endColumn' => 48,
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
 * Add nullable creation and update timestamps to the table.
 *
 * @param  int|null  $precision
 * @return \\Illuminate\\Support\\Collection<int, \\Illuminate\\Database\\Schema\\ColumnDefinition>
 */',
        'startLine' => 1272,
        'endLine' => 1278,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'nullableTimestamps' => 
      array (
        'name' => 'nullableTimestamps',
        'parameters' => 
        array (
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1288,
                'endLine' => 1288,
                'startTokenPos' => 4512,
                'startFilePos' => 36356,
                'endTokenPos' => 4512,
                'endFilePos' => 36359,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1288,
            'endLine' => 1288,
            'startColumn' => 40,
            'endColumn' => 56,
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
 * Add nullable creation and update timestamps to the table.
 *
 * Alias for self::timestamps().
 *
 * @param  int|null  $precision
 * @return \\Illuminate\\Support\\Collection<int, \\Illuminate\\Database\\Schema\\ColumnDefinition>
 */',
        'startLine' => 1288,
        'endLine' => 1291,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'timestampsTz' => 
      array (
        'name' => 'timestampsTz',
        'parameters' => 
        array (
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1299,
                'endLine' => 1299,
                'startTokenPos' => 4541,
                'startFilePos' => 36697,
                'endTokenPos' => 4541,
                'endFilePos' => 36700,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1299,
            'endLine' => 1299,
            'startColumn' => 34,
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
 * Add nullable creation and update timestampTz columns to the table.
 *
 * @param  int|null  $precision
 * @return \\Illuminate\\Support\\Collection<int, \\Illuminate\\Database\\Schema\\ColumnDefinition>
 */',
        'startLine' => 1299,
        'endLine' => 1305,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'nullableTimestampsTz' => 
      array (
        'name' => 'nullableTimestampsTz',
        'parameters' => 
        array (
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1315,
                'endLine' => 1315,
                'startTokenPos' => 4602,
                'startFilePos' => 37230,
                'endTokenPos' => 4602,
                'endFilePos' => 37233,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1315,
            'endLine' => 1315,
            'startColumn' => 42,
            'endColumn' => 58,
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
 * Add nullable creation and update timestampTz columns to the table.
 *
 * Alias for self::timestampsTz().
 *
 * @param  int|null  $precision
 * @return \\Illuminate\\Support\\Collection<int, \\Illuminate\\Database\\Schema\\ColumnDefinition>
 */',
        'startLine' => 1315,
        'endLine' => 1318,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'datetimes' => 
      array (
        'name' => 'datetimes',
        'parameters' => 
        array (
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1326,
                'endLine' => 1326,
                'startTokenPos' => 4631,
                'startFilePos' => 37558,
                'endTokenPos' => 4631,
                'endFilePos' => 37561,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1326,
            'endLine' => 1326,
            'startColumn' => 31,
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
 * Add creation and update datetime columns to the table.
 *
 * @param  int|null  $precision
 * @return \\Illuminate\\Support\\Collection<int, \\Illuminate\\Database\\Schema\\ColumnDefinition>
 */',
        'startLine' => 1326,
        'endLine' => 1332,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'softDeletes' => 
      array (
        'name' => 'softDeletes',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'deleted_at\'',
              'attributes' => 
              array (
                'startLine' => 1341,
                'endLine' => 1341,
                'startTokenPos' => 4692,
                'startFilePos' => 37998,
                'endTokenPos' => 4692,
                'endFilePos' => 38009,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1341,
            'endLine' => 1341,
            'startColumn' => 33,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1341,
                'endLine' => 1341,
                'startTokenPos' => 4699,
                'startFilePos' => 38025,
                'endTokenPos' => 4699,
                'endFilePos' => 38028,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1341,
            'endLine' => 1341,
            'startColumn' => 57,
            'endColumn' => 73,
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
 * Add a "deleted at" timestamp for the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1341,
        'endLine' => 1344,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'softDeletesTz' => 
      array (
        'name' => 'softDeletesTz',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'deleted_at\'',
              'attributes' => 
              array (
                'startLine' => 1353,
                'endLine' => 1353,
                'startTokenPos' => 4735,
                'startFilePos' => 38357,
                'endTokenPos' => 4735,
                'endFilePos' => 38368,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1353,
            'endLine' => 1353,
            'startColumn' => 35,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1353,
                'endLine' => 1353,
                'startTokenPos' => 4742,
                'startFilePos' => 38384,
                'endTokenPos' => 4742,
                'endFilePos' => 38387,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1353,
            'endLine' => 1353,
            'startColumn' => 59,
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
 * Add a "deleted at" timestampTz for the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1353,
        'endLine' => 1356,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'softDeletesDatetime' => 
      array (
        'name' => 'softDeletesDatetime',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'deleted_at\'',
              'attributes' => 
              array (
                'startLine' => 1365,
                'endLine' => 1365,
                'startTokenPos' => 4778,
                'startFilePos' => 38727,
                'endTokenPos' => 4778,
                'endFilePos' => 38738,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1365,
            'endLine' => 1365,
            'startColumn' => 41,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'precision' => 
          array (
            'name' => 'precision',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1365,
                'endLine' => 1365,
                'startTokenPos' => 4785,
                'startFilePos' => 38754,
                'endTokenPos' => 4785,
                'endFilePos' => 38757,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1365,
            'endLine' => 1365,
            'startColumn' => 65,
            'endColumn' => 81,
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
 * Add a "deleted at" datetime column to the table.
 *
 * @param  string  $column
 * @param  int|null  $precision
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1365,
        'endLine' => 1368,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'year' => 
      array (
        'name' => 'year',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1376,
            'endLine' => 1376,
            'startColumn' => 26,
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
 * Create a new year column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1376,
        'endLine' => 1379,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'binary' => 
      array (
        'name' => 'binary',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1389,
            'endLine' => 1389,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1389,
                'endLine' => 1389,
                'startTokenPos' => 4852,
                'startFilePos' => 39364,
                'endTokenPos' => 4852,
                'endFilePos' => 39367,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1389,
            'endLine' => 1389,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'fixed' => 
          array (
            'name' => 'fixed',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 1389,
                'endLine' => 1389,
                'startTokenPos' => 4859,
                'startFilePos' => 39379,
                'endTokenPos' => 4859,
                'endFilePos' => 39383,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1389,
            'endLine' => 1389,
            'startColumn' => 53,
            'endColumn' => 66,
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
 * Create a new binary column on the table.
 *
 * @param  string  $column
 * @param  int|null  $length
 * @param  bool  $fixed
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1389,
        'endLine' => 1392,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'uuid' => 
      array (
        'name' => 'uuid',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'uuid\'',
              'attributes' => 
              array (
                'startLine' => 1400,
                'endLine' => 1400,
                'startTokenPos' => 4900,
                'startFilePos' => 39674,
                'endTokenPos' => 4900,
                'endFilePos' => 39679,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1400,
            'endLine' => 1400,
            'startColumn' => 26,
            'endColumn' => 41,
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
 * Create a new UUID column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1400,
        'endLine' => 1403,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'foreignUuid' => 
      array (
        'name' => 'foreignUuid',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1411,
            'endLine' => 1411,
            'startColumn' => 33,
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
 * Create a new UUID column on the table with a foreign key constraint.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ForeignIdColumnDefinition
 */',
        'startLine' => 1411,
        'endLine' => 1417,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'ulid' => 
      array (
        'name' => 'ulid',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'ulid\'',
              'attributes' => 
              array (
                'startLine' => 1426,
                'endLine' => 1426,
                'startTokenPos' => 4981,
                'startFilePos' => 40381,
                'endTokenPos' => 4981,
                'endFilePos' => 40386,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1426,
            'endLine' => 1426,
            'startColumn' => 26,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => '26',
              'attributes' => 
              array (
                'startLine' => 1426,
                'endLine' => 1426,
                'startTokenPos' => 4988,
                'startFilePos' => 40399,
                'endTokenPos' => 4988,
                'endFilePos' => 40400,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1426,
            'endLine' => 1426,
            'startColumn' => 44,
            'endColumn' => 55,
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
 * Create a new ULID column on the table.
 *
 * @param  string  $column
 * @param  int|null  $length
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1426,
        'endLine' => 1429,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'foreignUlid' => 
      array (
        'name' => 'foreignUlid',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1438,
            'endLine' => 1438,
            'startColumn' => 33,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => '26',
              'attributes' => 
              array (
                'startLine' => 1438,
                'endLine' => 1438,
                'startTokenPos' => 5023,
                'startFilePos' => 40745,
                'endTokenPos' => 5023,
                'endFilePos' => 40746,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1438,
            'endLine' => 1438,
            'startColumn' => 42,
            'endColumn' => 53,
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
 * Create a new ULID column on the table with a foreign key constraint.
 *
 * @param  string  $column
 * @param  int|null  $length
 * @return \\Illuminate\\Database\\Schema\\ForeignIdColumnDefinition
 */',
        'startLine' => 1438,
        'endLine' => 1445,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'ipAddress' => 
      array (
        'name' => 'ipAddress',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'ip_address\'',
              'attributes' => 
              array (
                'startLine' => 1453,
                'endLine' => 1453,
                'startTokenPos' => 5083,
                'startFilePos' => 41156,
                'endTokenPos' => 5083,
                'endFilePos' => 41167,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1453,
            'endLine' => 1453,
            'startColumn' => 31,
            'endColumn' => 52,
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
 * Create a new IP address column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1453,
        'endLine' => 1456,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'macAddress' => 
      array (
        'name' => 'macAddress',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => 
            array (
              'code' => '\'mac_address\'',
              'attributes' => 
              array (
                'startLine' => 1464,
                'endLine' => 1464,
                'startTokenPos' => 5115,
                'startFilePos' => 41446,
                'endTokenPos' => 5115,
                'endFilePos' => 41458,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1464,
            'endLine' => 1464,
            'startColumn' => 32,
            'endColumn' => 54,
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
 * Create a new MAC address column on the table.
 *
 * @param  string  $column
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1464,
        'endLine' => 1467,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'geometry' => 
      array (
        'name' => 'geometry',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1477,
            'endLine' => 1477,
            'startColumn' => 30,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'subtype' => 
          array (
            'name' => 'subtype',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1477,
                'endLine' => 1477,
                'startTokenPos' => 5150,
                'startFilePos' => 41806,
                'endTokenPos' => 5150,
                'endFilePos' => 41809,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1477,
            'endLine' => 1477,
            'startColumn' => 39,
            'endColumn' => 53,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'srid' => 
          array (
            'name' => 'srid',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 1477,
                'endLine' => 1477,
                'startTokenPos' => 5157,
                'startFilePos' => 41820,
                'endTokenPos' => 5157,
                'endFilePos' => 41820,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1477,
            'endLine' => 1477,
            'startColumn' => 56,
            'endColumn' => 64,
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
 * Create a new geometry column on the table.
 *
 * @param  string  $column
 * @param  string|null  $subtype
 * @param  int  $srid
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1477,
        'endLine' => 1480,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'geography' => 
      array (
        'name' => 'geography',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1490,
            'endLine' => 1490,
            'startColumn' => 31,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'subtype' => 
          array (
            'name' => 'subtype',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1490,
                'endLine' => 1490,
                'startTokenPos' => 5201,
                'startFilePos' => 42196,
                'endTokenPos' => 5201,
                'endFilePos' => 42199,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1490,
            'endLine' => 1490,
            'startColumn' => 40,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'srid' => 
          array (
            'name' => 'srid',
            'default' => 
            array (
              'code' => '4326',
              'attributes' => 
              array (
                'startLine' => 1490,
                'endLine' => 1490,
                'startTokenPos' => 5208,
                'startFilePos' => 42210,
                'endTokenPos' => 5208,
                'endFilePos' => 42213,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1490,
            'endLine' => 1490,
            'startColumn' => 57,
            'endColumn' => 68,
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
 * Create a new geography column on the table.
 *
 * @param  string  $column
 * @param  string|null  $subtype
 * @param  int  $srid
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1490,
        'endLine' => 1493,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'computed' => 
      array (
        'name' => 'computed',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1502,
            'endLine' => 1502,
            'startColumn' => 30,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expression' => 
          array (
            'name' => 'expression',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1502,
            'endLine' => 1502,
            'startColumn' => 39,
            'endColumn' => 49,
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
 * Create a new generated, computed column on the table.
 *
 * @param  string  $column
 * @param  string  $expression
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1502,
        'endLine' => 1505,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'vector' => 
      array (
        'name' => 'vector',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1514,
            'endLine' => 1514,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dimensions' => 
          array (
            'name' => 'dimensions',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1514,
                'endLine' => 1514,
                'startTokenPos' => 5289,
                'startFilePos' => 42912,
                'endTokenPos' => 5289,
                'endFilePos' => 42915,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1514,
            'endLine' => 1514,
            'startColumn' => 37,
            'endColumn' => 54,
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
 * Create a new vector column on the table.
 *
 * @param  string  $column
 * @param  int|null  $dimensions
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1514,
        'endLine' => 1519,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'morphs' => 
      array (
        'name' => 'morphs',
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
            'startLine' => 1529,
            'endLine' => 1529,
            'startColumn' => 28,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1529,
                'endLine' => 1529,
                'startTokenPos' => 5346,
                'startFilePos' => 43303,
                'endTokenPos' => 5346,
                'endFilePos' => 43306,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1529,
            'endLine' => 1529,
            'startColumn' => 35,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'after' => 
          array (
            'name' => 'after',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1529,
                'endLine' => 1529,
                'startTokenPos' => 5353,
                'startFilePos' => 43318,
                'endTokenPos' => 5353,
                'endFilePos' => 43321,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1529,
            'endLine' => 1529,
            'startColumn' => 54,
            'endColumn' => 66,
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
 * Add the proper columns for a polymorphic table.
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @param  string|null  $after
 * @return void
 */',
        'startLine' => 1529,
        'endLine' => 1538,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'nullableMorphs' => 
      array (
        'name' => 'nullableMorphs',
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
            'startLine' => 1548,
            'endLine' => 1548,
            'startColumn' => 36,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1548,
                'endLine' => 1548,
                'startTokenPos' => 5455,
                'startFilePos' => 43913,
                'endTokenPos' => 5455,
                'endFilePos' => 43916,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1548,
            'endLine' => 1548,
            'startColumn' => 43,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'after' => 
          array (
            'name' => 'after',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1548,
                'endLine' => 1548,
                'startTokenPos' => 5462,
                'startFilePos' => 43928,
                'endTokenPos' => 5462,
                'endFilePos' => 43931,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1548,
            'endLine' => 1548,
            'startColumn' => 62,
            'endColumn' => 74,
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
 * Add nullable columns for a polymorphic table.
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @param  string|null  $after
 * @return void
 */',
        'startLine' => 1548,
        'endLine' => 1557,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'numericMorphs' => 
      array (
        'name' => 'numericMorphs',
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
            'startLine' => 1567,
            'endLine' => 1567,
            'startColumn' => 35,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1567,
                'endLine' => 1567,
                'startTokenPos' => 5564,
                'startFilePos' => 44580,
                'endTokenPos' => 5564,
                'endFilePos' => 44583,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1567,
            'endLine' => 1567,
            'startColumn' => 42,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'after' => 
          array (
            'name' => 'after',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1567,
                'endLine' => 1567,
                'startTokenPos' => 5571,
                'startFilePos' => 44595,
                'endTokenPos' => 5571,
                'endFilePos' => 44598,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1567,
            'endLine' => 1567,
            'startColumn' => 61,
            'endColumn' => 73,
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
 * Add the proper columns for a polymorphic table using numeric IDs (incremental).
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @param  string|null  $after
 * @return void
 */',
        'startLine' => 1567,
        'endLine' => 1576,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'nullableNumericMorphs' => 
      array (
        'name' => 'nullableNumericMorphs',
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
            'startLine' => 1586,
            'endLine' => 1586,
            'startColumn' => 43,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1586,
                'endLine' => 1586,
                'startTokenPos' => 5675,
                'startFilePos' => 45154,
                'endTokenPos' => 5675,
                'endFilePos' => 45157,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1586,
            'endLine' => 1586,
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'after' => 
          array (
            'name' => 'after',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1586,
                'endLine' => 1586,
                'startTokenPos' => 5682,
                'startFilePos' => 45169,
                'endTokenPos' => 5682,
                'endFilePos' => 45172,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1586,
            'endLine' => 1586,
            'startColumn' => 69,
            'endColumn' => 81,
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
 * Add nullable columns for a polymorphic table using numeric IDs (incremental).
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @param  string|null  $after
 * @return void
 */',
        'startLine' => 1586,
        'endLine' => 1597,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'uuidMorphs' => 
      array (
        'name' => 'uuidMorphs',
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
            'startLine' => 1607,
            'endLine' => 1607,
            'startColumn' => 32,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1607,
                'endLine' => 1607,
                'startTokenPos' => 5796,
                'startFilePos' => 45749,
                'endTokenPos' => 5796,
                'endFilePos' => 45752,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1607,
            'endLine' => 1607,
            'startColumn' => 39,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'after' => 
          array (
            'name' => 'after',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1607,
                'endLine' => 1607,
                'startTokenPos' => 5803,
                'startFilePos' => 45764,
                'endTokenPos' => 5803,
                'endFilePos' => 45767,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1607,
            'endLine' => 1607,
            'startColumn' => 58,
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
 * Add the proper columns for a polymorphic table using UUIDs.
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @param  string|null  $after
 * @return void
 */',
        'startLine' => 1607,
        'endLine' => 1616,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'nullableUuidMorphs' => 
      array (
        'name' => 'nullableUuidMorphs',
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
            'startLine' => 1626,
            'endLine' => 1626,
            'startColumn' => 40,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1626,
                'endLine' => 1626,
                'startTokenPos' => 5907,
                'startFilePos' => 46286,
                'endTokenPos' => 5907,
                'endFilePos' => 46289,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1626,
            'endLine' => 1626,
            'startColumn' => 47,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'after' => 
          array (
            'name' => 'after',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1626,
                'endLine' => 1626,
                'startTokenPos' => 5914,
                'startFilePos' => 46301,
                'endTokenPos' => 5914,
                'endFilePos' => 46304,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1626,
            'endLine' => 1626,
            'startColumn' => 66,
            'endColumn' => 78,
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
 * Add nullable columns for a polymorphic table using UUIDs.
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @param  string|null  $after
 * @return void
 */',
        'startLine' => 1626,
        'endLine' => 1637,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'ulidMorphs' => 
      array (
        'name' => 'ulidMorphs',
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
            'startLine' => 1647,
            'endLine' => 1647,
            'startColumn' => 32,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1647,
                'endLine' => 1647,
                'startTokenPos' => 6028,
                'startFilePos' => 46867,
                'endTokenPos' => 6028,
                'endFilePos' => 46870,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1647,
            'endLine' => 1647,
            'startColumn' => 39,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'after' => 
          array (
            'name' => 'after',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1647,
                'endLine' => 1647,
                'startTokenPos' => 6035,
                'startFilePos' => 46882,
                'endTokenPos' => 6035,
                'endFilePos' => 46885,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1647,
            'endLine' => 1647,
            'startColumn' => 58,
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
 * Add the proper columns for a polymorphic table using ULIDs.
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @param  string|null  $after
 * @return void
 */',
        'startLine' => 1647,
        'endLine' => 1656,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'nullableUlidMorphs' => 
      array (
        'name' => 'nullableUlidMorphs',
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
            'startLine' => 1666,
            'endLine' => 1666,
            'startColumn' => 40,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'indexName' => 
          array (
            'name' => 'indexName',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1666,
                'endLine' => 1666,
                'startTokenPos' => 6139,
                'startFilePos' => 47404,
                'endTokenPos' => 6139,
                'endFilePos' => 47407,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1666,
            'endLine' => 1666,
            'startColumn' => 47,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'after' => 
          array (
            'name' => 'after',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1666,
                'endLine' => 1666,
                'startTokenPos' => 6146,
                'startFilePos' => 47419,
                'endTokenPos' => 6146,
                'endFilePos' => 47422,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1666,
            'endLine' => 1666,
            'startColumn' => 66,
            'endColumn' => 78,
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
 * Add nullable columns for a polymorphic table using ULIDs.
 *
 * @param  string  $name
 * @param  string|null  $indexName
 * @param  string|null  $after
 * @return void
 */',
        'startLine' => 1666,
        'endLine' => 1677,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'rememberToken' => 
      array (
        'name' => 'rememberToken',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add the `remember_token` column to the table.
 *
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1684,
        'endLine' => 1687,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'rawColumn' => 
      array (
        'name' => 'rawColumn',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1696,
            'endLine' => 1696,
            'startColumn' => 31,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'definition' => 
          array (
            'name' => 'definition',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1696,
            'endLine' => 1696,
            'startColumn' => 40,
            'endColumn' => 50,
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
 * Create a new custom column on the table.
 *
 * @param  string  $column
 * @param  string  $definition
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1696,
        'endLine' => 1699,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'comment' => 
      array (
        'name' => 'comment',
        'parameters' => 
        array (
          'comment' => 
          array (
            'name' => 'comment',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1707,
            'endLine' => 1707,
            'startColumn' => 29,
            'endColumn' => 36,
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
 * Add a comment to the table.
 *
 * @param  string  $comment
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 1707,
        'endLine' => 1710,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'indexCommand' => 
      array (
        'name' => 'indexCommand',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1722,
            'endLine' => 1722,
            'startColumn' => 37,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'columns' => 
          array (
            'name' => 'columns',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1722,
            'endLine' => 1722,
            'startColumn' => 44,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1722,
            'endLine' => 1722,
            'startColumn' => 54,
            'endColumn' => 59,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'algorithm' => 
          array (
            'name' => 'algorithm',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1722,
                'endLine' => 1722,
                'startTokenPos' => 6365,
                'startFilePos' => 48928,
                'endTokenPos' => 6365,
                'endFilePos' => 48931,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1722,
            'endLine' => 1722,
            'startColumn' => 62,
            'endColumn' => 78,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'operatorClass' => 
          array (
            'name' => 'operatorClass',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1722,
                'endLine' => 1722,
                'startTokenPos' => 6372,
                'startFilePos' => 48951,
                'endTokenPos' => 6372,
                'endFilePos' => 48954,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1722,
            'endLine' => 1722,
            'startColumn' => 81,
            'endColumn' => 101,
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
 * Create a new index command on the blueprint.
 *
 * @param  string  $type
 * @param  string|array  $columns
 * @param  string  $index
 * @param  string|null  $algorithm
 * @param  string|null  $operatorClass
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 1722,
        'endLine' => 1734,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'dropIndexCommand' => 
      array (
        'name' => 'dropIndexCommand',
        'parameters' => 
        array (
          'command' => 
          array (
            'name' => 'command',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1744,
            'endLine' => 1744,
            'startColumn' => 41,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1744,
            'endLine' => 1744,
            'startColumn' => 51,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'index' => 
          array (
            'name' => 'index',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1744,
            'endLine' => 1744,
            'startColumn' => 58,
            'endColumn' => 63,
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
 * Create a new drop index command on the blueprint.
 *
 * @param  string  $command
 * @param  string  $type
 * @param  string|array  $index
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 1744,
        'endLine' => 1756,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'createIndexName' => 
      array (
        'name' => 'createIndexName',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1765,
            'endLine' => 1765,
            'startColumn' => 40,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'columns' => 
          array (
            'name' => 'columns',
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
            'startLine' => 1765,
            'endLine' => 1765,
            'startColumn' => 47,
            'endColumn' => 60,
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
 * Create a default index name for the table.
 *
 * @param  string  $type
 * @param  array  $columns
 * @return string
 */',
        'startLine' => 1765,
        'endLine' => 1778,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'addColumn' => 
      array (
        'name' => 'addColumn',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1788,
            'endLine' => 1788,
            'startColumn' => 31,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 1788,
            'endLine' => 1788,
            'startColumn' => 38,
            'endColumn' => 42,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 1788,
                'endLine' => 1788,
                'startTokenPos' => 6696,
                'startFilePos' => 51174,
                'endTokenPos' => 6697,
                'endFilePos' => 51175,
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
            'startLine' => 1788,
            'endLine' => 1788,
            'startColumn' => 45,
            'endColumn' => 66,
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
 * Add a new column to the blueprint.
 *
 * @param  string  $type
 * @param  string  $name
 * @param  array  $parameters
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1788,
        'endLine' => 1793,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'addColumnDefinition' => 
      array (
        'name' => 'addColumnDefinition',
        'parameters' => 
        array (
          'definition' => 
          array (
            'name' => 'definition',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1801,
            'endLine' => 1801,
            'startColumn' => 44,
            'endColumn' => 54,
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
 * Add a new column definition to the blueprint.
 *
 * @param  \\Illuminate\\Database\\Schema\\ColumnDefinition  $definition
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition
 */',
        'startLine' => 1801,
        'endLine' => 1816,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'after' => 
      array (
        'name' => 'after',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1825,
            'endLine' => 1825,
            'startColumn' => 27,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1825,
            'endLine' => 1825,
            'startColumn' => 36,
            'endColumn' => 52,
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
 * Add the columns from the callback after the given column.
 *
 * @param  string  $column
 * @param  (\\Closure(self): void)  $callback
 * @return void
 */',
        'startLine' => 1825,
        'endLine' => 1832,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'removeColumn' => 
      array (
        'name' => 'removeColumn',
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
            'startLine' => 1840,
            'endLine' => 1840,
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
 * Remove a column from the schema blueprint.
 *
 * @param  string  $name
 * @return $this
 */',
        'startLine' => 1840,
        'endLine' => 1851,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'addCommand' => 
      array (
        'name' => 'addCommand',
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
            'startLine' => 1860,
            'endLine' => 1860,
            'startColumn' => 35,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 1860,
                'endLine' => 1860,
                'startTokenPos' => 7007,
                'startFilePos' => 53009,
                'endTokenPos' => 7008,
                'endFilePos' => 53010,
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
            'startLine' => 1860,
            'endLine' => 1860,
            'startColumn' => 42,
            'endColumn' => 63,
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
 * Add a new command to the blueprint.
 *
 * @param  string  $name
 * @param  array  $parameters
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 1860,
        'endLine' => 1865,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'createCommand' => 
      array (
        'name' => 'createCommand',
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
            'startLine' => 1874,
            'endLine' => 1874,
            'startColumn' => 38,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 1874,
                'endLine' => 1874,
                'startTokenPos' => 7060,
                'startFilePos' => 53361,
                'endTokenPos' => 7061,
                'endFilePos' => 53362,
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
            'startLine' => 1874,
            'endLine' => 1874,
            'startColumn' => 45,
            'endColumn' => 66,
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
 * Create a new Fluent command.
 *
 * @param  string  $name
 * @param  array  $parameters
 * @return \\Illuminate\\Support\\Fluent
 */',
        'startLine' => 1874,
        'endLine' => 1877,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'getTable' => 
      array (
        'name' => 'getTable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the table the blueprint describes.
 *
 * @return string
 */',
        'startLine' => 1884,
        'endLine' => 1887,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'getPrefix' => 
      array (
        'name' => 'getPrefix',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the table prefix.
 *
 * @deprecated Use DB::getTablePrefix()
 *
 * @return string
 */',
        'startLine' => 1896,
        'endLine' => 1899,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'getColumns' => 
      array (
        'name' => 'getColumns',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the columns on the blueprint.
 *
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition[]
 */',
        'startLine' => 1906,
        'endLine' => 1909,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'getCommands' => 
      array (
        'name' => 'getCommands',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the commands on the blueprint.
 *
 * @return \\Illuminate\\Support\\Fluent[]
 */',
        'startLine' => 1916,
        'endLine' => 1919,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'hasState' => 
      array (
        'name' => 'hasState',
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
        ),
        'docComment' => '/**
 * Determine if the blueprint has state.
 *
 * @return bool
 */',
        'startLine' => 1926,
        'endLine' => 1929,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'getState' => 
      array (
        'name' => 'getState',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the state of the blueprint.
 *
 * @return \\Illuminate\\Database\\Schema\\BlueprintState
 */',
        'startLine' => 1936,
        'endLine' => 1939,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'getAddedColumns' => 
      array (
        'name' => 'getAddedColumns',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the columns on the blueprint that should be added.
 *
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition[]
 */',
        'startLine' => 1946,
        'endLine' => 1951,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'getChangedColumns' => 
      array (
        'name' => 'getChangedColumns',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the columns on the blueprint that should be changed.
 *
 * @deprecated Will be removed in a future Laravel version.
 *
 * @return \\Illuminate\\Database\\Schema\\ColumnDefinition[]
 */',
        'startLine' => 1960,
        'endLine' => 1965,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'aliasName' => NULL,
      ),
      'defaultTimePrecision' => 
      array (
        'name' => 'defaultTimePrecision',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default time precision.
 */',
        'startLine' => 1970,
        'endLine' => 1973,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Blueprint',
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