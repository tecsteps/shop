<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Schema/SQLiteBuilder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Schema\SQLiteBuilder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-32ec0ac434c065225709d3a05affe378463d8d37bab82ae1b8c55affbd7008b6-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Schema/SQLiteBuilder.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Schema',
    'name' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
    'shortName' => 'SQLiteBuilder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 9,
    'endLine' => 173,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Schema\\Builder',
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
      'createDatabase' => 
      array (
        'name' => 'createDatabase',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 36,
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
 * Create a database in the schema.
 *
 * @param  string  $name
 * @return bool
 */',
        'startLine' => 17,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'dropDatabaseIfExists' => 
      array (
        'name' => 'dropDatabaseIfExists',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 42,
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
 * Drop a database from the schema if the database exists.
 *
 * @param  string  $name
 * @return bool
 */',
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'getTables' => 
      array (
        'name' => 'getTables',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 112,
                'startFilePos' => 724,
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @inheritDoc */',
        'startLine' => 34,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'getViews' => 
      array (
        'name' => 'getViews',
        'parameters' => 
        array (
          'schema' => 
          array (
            'name' => 'schema',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 64,
                'endLine' => 64,
                'startTokenPos' => 328,
                'startFilePos' => 1731,
                'endTokenPos' => 328,
                'endFilePos' => 1734,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 30,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @inheritDoc */',
        'startLine' => 64,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'getColumns' => 
      array (
        'name' => 'getColumns',
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
            'startLine' => 80,
            'endLine' => 80,
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
        'docComment' => '/** @inheritDoc */',
        'startLine' => 80,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'dropAllTables' => 
      array (
        'name' => 'dropAllTables',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Drop all tables from the database.
 *
 * @return void
 */',
        'startLine' => 97,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'dropAllViews' => 
      array (
        'name' => 'dropAllViews',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Drop all views from the database.
 *
 * @return void
 */',
        'startLine' => 126,
        'endLine' => 137,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'pragma' => 
      array (
        'name' => 'pragma',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 28,
            'endColumn' => 31,
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
                'startLine' => 146,
                'endLine' => 146,
                'startTokenPos' => 818,
                'startFilePos' => 4265,
                'endTokenPos' => 818,
                'endFilePos' => 4268,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 34,
            'endColumn' => 46,
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
 * Get the value for the given pragma name or set the given value.
 *
 * @param  string  $key
 * @param  mixed  $value
 * @return mixed
 */',
        'startLine' => 146,
        'endLine' => 151,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'refreshDatabaseFile' => 
      array (
        'name' => 'refreshDatabaseFile',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 159,
                'endLine' => 159,
                'startTokenPos' => 884,
                'startFilePos' => 4624,
                'endTokenPos' => 884,
                'endFilePos' => 4627,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 41,
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
 * Empty the database file.
 *
 * @param  string|null  $path
 * @return void
 */',
        'startLine' => 159,
        'endLine' => 162,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'aliasName' => NULL,
      ),
      'getCurrentSchemaListing' => 
      array (
        'name' => 'getCurrentSchemaListing',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the names of current schemas for the connection.
 *
 * @return string[]|null
 */',
        'startLine' => 169,
        'endLine' => 172,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Schema',
        'declaringClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'implementingClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
        'currentClassName' => 'Illuminate\\Database\\Schema\\SQLiteBuilder',
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