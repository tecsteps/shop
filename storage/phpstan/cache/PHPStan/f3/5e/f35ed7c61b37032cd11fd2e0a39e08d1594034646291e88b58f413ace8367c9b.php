<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Query/Processors/Processor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Query\Processors\Processor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-5968f352561c8f1d68a2d96ee5694b283243410d0a4e9fa82c9cb8b9a638c2ee-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Query/Processors/Processor.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Query\\Processors',
    'name' => 'Illuminate\\Database\\Query\\Processors\\Processor',
    'shortName' => 'Processor',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 7,
    'endLine' => 144,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
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
      'processSelect' => 
      array (
        'name' => 'processSelect',
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
                'name' => 'Illuminate\\Database\\Query\\Builder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 51,
            'endColumn' => 58,
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
 * Process the results of a "select" query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $results
 * @return array
 */',
        'startLine' => 16,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'aliasName' => NULL,
      ),
      'processInsertGetId' => 
      array (
        'name' => 'processInsertGetId',
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
                'name' => 'Illuminate\\Database\\Query\\Builder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 40,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 56,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'values' => 
          array (
            'name' => 'values',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 62,
            'endColumn' => 68,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'sequence' => 
          array (
            'name' => 'sequence',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 30,
                'endLine' => 30,
                'startTokenPos' => 66,
                'startFilePos' => 713,
                'endTokenPos' => 66,
                'endFilePos' => 716,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 71,
            'endColumn' => 86,
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
 * Process an  "insert get ID" query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  string  $sql
 * @param  array  $values
 * @param  string|null  $sequence
 * @return int
 */',
        'startLine' => 30,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'aliasName' => NULL,
      ),
      'processSchemas' => 
      array (
        'name' => 'processSchemas',
        'parameters' => 
        array (
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 36,
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
 * Process the results of a schemas query.
 *
 * @param  list<array<string, mixed>>  $results
 * @return list<array{name: string, path: string|null, default: bool}>
 */',
        'startLine' => 45,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'aliasName' => NULL,
      ),
      'processTables' => 
      array (
        'name' => 'processTables',
        'parameters' => 
        array (
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 35,
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
 * Process the results of a tables query.
 *
 * @param  list<array<string, mixed>>  $results
 * @return list<array{name: string, schema: string|null, schema_qualified_name: string, size: int|null, comment: string|null, collation: string|null, engine: string|null}>
 */',
        'startLine' => 64,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'aliasName' => NULL,
      ),
      'processViews' => 
      array (
        'name' => 'processViews',
        'parameters' => 
        array (
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 34,
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
 * Process the results of a views query.
 *
 * @param  list<array<string, mixed>>  $results
 * @return list<array{name: string, schema: string, schema_qualified_name: string, definition: string}>
 */',
        'startLine' => 87,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'aliasName' => NULL,
      ),
      'processTypes' => 
      array (
        'name' => 'processTypes',
        'parameters' => 
        array (
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 34,
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
 * Process the results of a types query.
 *
 * @param  list<array<string, mixed>>  $results
 * @return list<array{name: string, schema: string, type: string, type: string, category: string, implicit: bool}>
 */',
        'startLine' => 107,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'aliasName' => NULL,
      ),
      'processColumns' => 
      array (
        'name' => 'processColumns',
        'parameters' => 
        array (
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 36,
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
 * Process the results of a columns query.
 *
 * @param  list<array<string, mixed>>  $results
 * @return list<array{name: string, type: string, type_name: string, nullable: bool, default: mixed, auto_increment: bool, comment: string|null, generation: array{type: string, expression: string|null}|null}>
 */',
        'startLine' => 118,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'aliasName' => NULL,
      ),
      'processIndexes' => 
      array (
        'name' => 'processIndexes',
        'parameters' => 
        array (
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 36,
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
 * Process the results of an indexes query.
 *
 * @param  list<array<string, mixed>>  $results
 * @return list<array{name: string, columns: list<string>, type: string, unique: bool, primary: bool}>
 */',
        'startLine' => 129,
        'endLine' => 132,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'aliasName' => NULL,
      ),
      'processForeignKeys' => 
      array (
        'name' => 'processForeignKeys',
        'parameters' => 
        array (
          'results' => 
          array (
            'name' => 'results',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 40,
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
 * Process the results of a foreign keys query.
 *
 * @param  list<array<string, mixed>>  $results
 * @return list<array{name: string, columns: list<string>, foreign_schema: string, foreign_table: string, foreign_columns: list<string>, on_update: string, on_delete: string}>
 */',
        'startLine' => 140,
        'endLine' => 143,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Processors',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
        'currentClassName' => 'Illuminate\\Database\\Query\\Processors\\Processor',
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