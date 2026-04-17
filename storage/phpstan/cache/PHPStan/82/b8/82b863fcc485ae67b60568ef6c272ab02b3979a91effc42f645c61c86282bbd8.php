<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Schema/Grammars/Grammar.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Schema\Grammars\Grammar
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6384485f04369c19005175d49814fec261e1149f0157821009ad890dec0ffc34-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Schema/Grammars/Grammar.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
    'name' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
    'shortName' => 'Grammar',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 524,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Grammar',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Concerns\\CompilesJsonPaths',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'modifiers' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'name' => 'modifiers',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 78,
            'startFilePos' => 544,
            'endTokenPos' => 79,
            'endFilePos' => 545,
          ),
        ),
        'docComment' => '/**
 * The possible column modifiers.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'transactions' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'name' => 'transactions',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 90,
            'startFilePos' => 692,
            'endTokenPos' => 90,
            'endFilePos' => 696,
          ),
        ),
        'docComment' => '/**
 * If this Grammar supports schema changes wrapped in a transaction.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fluentCommands' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'name' => 'fluentCommands',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 101,
            'startFilePos' => 844,
            'endTokenPos' => 102,
            'endFilePos' => 845,
          ),
        ),
        'docComment' => '/**
 * The commands to be executed outside of create or alter command.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 35,
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
      'compileCreateDatabase' => 
      array (
        'name' => 'compileCreateDatabase',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 43,
            'endColumn' => 47,
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
 * Compile a create database command.
 *
 * @param  string  $name
 * @return string
 */',
        'startLine' => 46,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileDropDatabaseIfExists' => 
      array (
        'name' => 'compileDropDatabaseIfExists',
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 49,
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
 * Compile a drop database if exists command.
 *
 * @param  string  $name
 * @return string
 */',
        'startLine' => 59,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileSchemas' => 
      array (
        'name' => 'compileSchemas',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compile the query to determine the schemas.
 *
 * @return string
 */',
        'startLine' => 71,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileTableExists' => 
      array (
        'name' => 'compileTableExists',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 40,
            'endColumn' => 46,
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 49,
            'endColumn' => 54,
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
 * Compile the query to determine if the given table exists.
 *
 * @param  string|null  $schema
 * @param  string  $table
 * @return string|null
 */',
        'startLine' => 83,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileTables' => 
      array (
        'name' => 'compileTables',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 96,
            'endLine' => 96,
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
 * Compile the query to determine the tables.
 *
 * @param  string|string[]|null  $schema
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 96,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileViews' => 
      array (
        'name' => 'compileViews',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 34,
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
 * Compile the query to determine the views.
 *
 * @param  string|string[]|null  $schema
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 109,
        'endLine' => 112,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileTypes' => 
      array (
        'name' => 'compileTypes',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 34,
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
 * Compile the query to determine the user-defined types.
 *
 * @param  string|string[]|null  $schema
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 122,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileColumns' => 
      array (
        'name' => 'compileColumns',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
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
            'startColumn' => 36,
            'endColumn' => 42,
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 45,
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
 * Compile the query to determine the columns.
 *
 * @param  string|null  $schema
 * @param  string  $table
 * @return string
 *
 * @throws \\RuntimeException
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
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileIndexes' => 
      array (
        'name' => 'compileIndexes',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 36,
            'endColumn' => 42,
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 45,
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
 * Compile the query to determine the indexes.
 *
 * @param  string|null  $schema
 * @param  string  $table
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 150,
        'endLine' => 153,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileVectorIndex' => 
      array (
        'name' => 'compileVectorIndex',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 40,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'command' => 
          array (
            'name' => 'command',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 62,
            'endColumn' => 76,
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
 * Compile a vector index key command.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Support\\Fluent  $command
 * @return void
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 164,
        'endLine' => 167,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileForeignKeys' => 
      array (
        'name' => 'compileForeignKeys',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 40,
            'endColumn' => 46,
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 49,
            'endColumn' => 54,
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
 * Compile the query to determine the foreign keys.
 *
 * @param  string|null  $schema
 * @param  string  $table
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 178,
        'endLine' => 181,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileRenameColumn' => 
      array (
        'name' => 'compileRenameColumn',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 41,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'command' => 
          array (
            'name' => 'command',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 63,
            'endColumn' => 77,
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
 * Compile a rename column command.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Support\\Fluent  $command
 * @return list<string>|string
 */',
        'startLine' => 190,
        'endLine' => 197,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileChange' => 
      array (
        'name' => 'compileChange',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 208,
            'endLine' => 208,
            'startColumn' => 35,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'command' => 
          array (
            'name' => 'command',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 208,
            'endLine' => 208,
            'startColumn' => 57,
            'endColumn' => 71,
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
 * Compile a change column command into a series of SQL statements.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Support\\Fluent  $command
 * @return list<string>|string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 208,
        'endLine' => 211,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileFulltext' => 
      array (
        'name' => 'compileFulltext',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 37,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'command' => 
          array (
            'name' => 'command',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 59,
            'endColumn' => 73,
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
 * Compile a fulltext index key command.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Support\\Fluent  $command
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 222,
        'endLine' => 225,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileDropFullText' => 
      array (
        'name' => 'compileDropFullText',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 41,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'command' => 
          array (
            'name' => 'command',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 236,
            'endLine' => 236,
            'startColumn' => 63,
            'endColumn' => 77,
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
 * Compile a drop fulltext index command.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Support\\Fluent  $command
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 236,
        'endLine' => 239,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileForeign' => 
      array (
        'name' => 'compileForeign',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 248,
            'endLine' => 248,
            'startColumn' => 36,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'command' => 
          array (
            'name' => 'command',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 248,
            'endLine' => 248,
            'startColumn' => 58,
            'endColumn' => 72,
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
 * Compile a foreign key command.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Support\\Fluent  $command
 * @return string
 */',
        'startLine' => 248,
        'endLine' => 279,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileDropForeign' => 
      array (
        'name' => 'compileDropForeign',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 288,
            'endLine' => 288,
            'startColumn' => 40,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'command' => 
          array (
            'name' => 'command',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 288,
            'endLine' => 288,
            'startColumn' => 62,
            'endColumn' => 76,
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
 * Compile a drop foreign key command.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Support\\Fluent  $command
 * @return string
 */',
        'startLine' => 288,
        'endLine' => 291,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getColumns' => 
      array (
        'name' => 'getColumns',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 299,
            'endLine' => 299,
            'startColumn' => 35,
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
 * Compile the blueprint\'s added column definitions.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @return array
 */',
        'startLine' => 299,
        'endLine' => 308,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getColumn' => 
      array (
        'name' => 'getColumn',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 317,
            'endLine' => 317,
            'startColumn' => 34,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 317,
            'endLine' => 317,
            'startColumn' => 56,
            'endColumn' => 62,
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
 * Compile the column definition.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Database\\Schema\\ColumnDefinition  $column
 * @return string
 */',
        'startLine' => 317,
        'endLine' => 325,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getType' => 
      array (
        'name' => 'getType',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 333,
            'endLine' => 333,
            'startColumn' => 32,
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
 * Get the SQL for the column data type.
 *
 * @param  \\Illuminate\\Support\\Fluent  $column
 * @return string
 */',
        'startLine' => 333,
        'endLine' => 336,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'typeComputed' => 
      array (
        'name' => 'typeComputed',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 346,
            'endLine' => 346,
            'startColumn' => 37,
            'endColumn' => 50,
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
 * Create the column definition for a generated, computed column type.
 *
 * @param  \\Illuminate\\Support\\Fluent  $column
 * @return void
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 346,
        'endLine' => 349,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'typeVector' => 
      array (
        'name' => 'typeVector',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 359,
            'endLine' => 359,
            'startColumn' => 35,
            'endColumn' => 48,
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
 * Create the column definition for a vector type.
 *
 * @param  \\Illuminate\\Support\\Fluent  $column
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 359,
        'endLine' => 362,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'typeRaw' => 
      array (
        'name' => 'typeRaw',
        'parameters' => 
        array (
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 370,
            'endLine' => 370,
            'startColumn' => 32,
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
 * Create the column definition for a raw column type.
 *
 * @param  \\Illuminate\\Support\\Fluent  $column
 * @return string
 */',
        'startLine' => 370,
        'endLine' => 373,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'addModifiers' => 
      array (
        'name' => 'addModifiers',
        'parameters' => 
        array (
          'sql' => 
          array (
            'name' => 'sql',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 383,
            'endLine' => 383,
            'startColumn' => 37,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 383,
            'endLine' => 383,
            'startColumn' => 43,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Fluent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 383,
            'endLine' => 383,
            'startColumn' => 65,
            'endColumn' => 78,
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
 * Add the column modifiers to the definition.
 *
 * @param  string  $sql
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  \\Illuminate\\Support\\Fluent  $column
 * @return string
 */',
        'startLine' => 383,
        'endLine' => 392,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getCommandByName' => 
      array (
        'name' => 'getCommandByName',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 41,
            'endColumn' => 60,
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
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 63,
            'endColumn' => 67,
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
 * Get the command with a given name if it exists on the blueprint.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  string  $name
 * @return \\Illuminate\\Support\\Fluent|null
 */',
        'startLine' => 401,
        'endLine' => 408,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getCommandsByName' => 
      array (
        'name' => 'getCommandsByName',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 417,
            'endLine' => 417,
            'startColumn' => 42,
            'endColumn' => 61,
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
            'startLine' => 417,
            'endLine' => 417,
            'startColumn' => 64,
            'endColumn' => 68,
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
 * Get all of the commands with a given name.
 *
 * @param  \\Illuminate\\Database\\Schema\\Blueprint  $blueprint
 * @param  string  $name
 * @return array
 */',
        'startLine' => 417,
        'endLine' => 422,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'hasCommand' => 
      array (
        'name' => 'hasCommand',
        'parameters' => 
        array (
          'blueprint' => 
          array (
            'name' => 'blueprint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Schema\\Blueprint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 431,
            'endLine' => 431,
            'startColumn' => 35,
            'endColumn' => 54,
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
            'startLine' => 431,
            'endLine' => 431,
            'startColumn' => 57,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 431,
        'endLine' => 440,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'prefixArray' => 
      array (
        'name' => 'prefixArray',
        'parameters' => 
        array (
          'prefix' => 
          array (
            'name' => 'prefix',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 449,
            'endLine' => 449,
            'startColumn' => 33,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'values' => 
          array (
            'name' => 'values',
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
            'startLine' => 449,
            'endLine' => 449,
            'startColumn' => 42,
            'endColumn' => 54,
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
 * Add a prefix to an array of values.
 *
 * @param  string  $prefix
 * @param  array<string>  $values
 * @return array<string>
 */',
        'startLine' => 449,
        'endLine' => 454,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'wrapTable' => 
      array (
        'name' => 'wrapTable',
        'parameters' => 
        array (
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
            'startLine' => 463,
            'endLine' => 463,
            'startColumn' => 31,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'prefix' => 
          array (
            'name' => 'prefix',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 463,
                'endLine' => 463,
                'startTokenPos' => 1357,
                'startFilePos' => 13169,
                'endTokenPos' => 1357,
                'endFilePos' => 13172,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 463,
            'endLine' => 463,
            'startColumn' => 39,
            'endColumn' => 52,
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
 * Wrap a table in keyword identifiers.
 *
 * @param  mixed  $table
 * @param  string|null  $prefix
 * @return string
 */',
        'startLine' => 463,
        'endLine' => 469,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'wrap' => 
      array (
        'name' => 'wrap',
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
            'startLine' => 477,
            'endLine' => 477,
            'startColumn' => 26,
            'endColumn' => 31,
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
 * Wrap a value in keyword identifiers.
 *
 * @param  \\Illuminate\\Support\\Fluent|\\Illuminate\\Contracts\\Database\\Query\\Expression|string  $value
 * @return string
 */',
        'startLine' => 477,
        'endLine' => 482,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getDefaultValue' => 
      array (
        'name' => 'getDefaultValue',
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
            'startLine' => 490,
            'endLine' => 490,
            'startColumn' => 40,
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
 * Format a value so that it can be used in "default" clauses.
 *
 * @param  mixed  $value
 * @return string
 */',
        'startLine' => 490,
        'endLine' => 503,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getFluentCommands' => 
      array (
        'name' => 'getFluentCommands',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the fluent commands for the grammar.
 *
 * @return array
 */',
        'startLine' => 510,
        'endLine' => 513,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'supportsSchemaTransactions' => 
      array (
        'name' => 'supportsSchemaTransactions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if this Grammar supports schema changes wrapped in a transaction.
 *
 * @return bool
 */',
        'startLine' => 520,
        'endLine' => 523,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Schema\\Grammars\\Grammar',
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