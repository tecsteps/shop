<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Query/Grammars/Grammar.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Query\Grammars\Grammar
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6d5cd35ba7522f6dbe2be5a5070daf2fad41c8d2fef5fd7824d1b61ef87aa8d8-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Query/Grammars/Grammar.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Query\\Grammars',
    'name' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
    'shortName' => 'Grammar',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 1600,
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
      'operators' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'name' => 'operators',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 79,
            'startFilePos' => 593,
            'endTokenPos' => 80,
            'endFilePos' => 594,
          ),
        ),
        'docComment' => '/**
 * The grammar specific operators.
 *
 * @var array
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
      'bitwiseOperators' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'name' => 'bitwiseOperators',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 91,
            'startFilePos' => 720,
            'endTokenPos' => 92,
            'endFilePos' => 721,
          ),
        ),
        'docComment' => '/**
 * The grammar specific bitwise operators.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'selectComponents' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'name' => 'selectComponents',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'aggregate\', \'columns\', \'from\', \'indexHint\', \'joins\', \'wheres\', \'groups\', \'havings\', \'orders\', \'limit\', \'offset\', \'lock\']',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 51,
            'startTokenPos' => 103,
            'startFilePos' => 855,
            'endTokenPos' => 141,
            'endFilePos' => 1079,
          ),
        ),
        'docComment' => '/**
 * The components that make up a select clause.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 6,
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
      'compileSelect' => 
      array (
        'name' => 'compileSelect',
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
            'startLine' => 59,
            'endLine' => 59,
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
 * Compile a select query into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return string
 */',
        'startLine' => 59,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileComponents' => 
      array (
        'name' => 'compileComponents',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * Compile the components necessary for a select clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return array
 */',
        'startLine' => 107,
        'endLine' => 120,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileAggregate' => 
      array (
        'name' => 'compileAggregate',
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
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 41,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'aggregate' => 
          array (
            'name' => 'aggregate',
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
            'startColumn' => 57,
            'endColumn' => 66,
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
 * Compile an aggregated select clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array{function: string, columns: array<\\Illuminate\\Contracts\\Database\\Query\\Expression|string>}  $aggregate
 * @return string
 */',
        'startLine' => 129,
        'endLine' => 143,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileColumns' => 
      array (
        'name' => 'compileColumns',
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
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 39,
            'endColumn' => 52,
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
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 55,
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
 * Compile the "select *" portion of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $columns
 * @return string|null
 */',
        'startLine' => 152,
        'endLine' => 168,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileFrom' => 
      array (
        'name' => 'compileFrom',
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 36,
            'endColumn' => 49,
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 52,
            'endColumn' => 57,
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
 * Compile the "from" portion of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  string  $table
 * @return string
 */',
        'startLine' => 177,
        'endLine' => 180,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileJoins' => 
      array (
        'name' => 'compileJoins',
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
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'joins' => 
          array (
            'name' => 'joins',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 189,
            'endLine' => 189,
            'startColumn' => 53,
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
 * Compile the "join" portions of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $joins
 * @return string
 */',
        'startLine' => 189,
        'endLine' => 204,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileJoinLateral' => 
      array (
        'name' => 'compileJoinLateral',
        'parameters' => 
        array (
          'join' => 
          array (
            'name' => 'join',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Query\\JoinLateralClause',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 40,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expression' => 
          array (
            'name' => 'expression',
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
            'startLine' => 215,
            'endLine' => 215,
            'startColumn' => 65,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compile a "lateral join" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\JoinLateralClause  $join
 * @param  string  $expression
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 215,
        'endLine' => 218,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileWheres' => 
      array (
        'name' => 'compileWheres',
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
            'startLine' => 226,
            'endLine' => 226,
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
 * Compile the "where" portions of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return string
 */',
        'startLine' => 226,
        'endLine' => 243,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileWheresToArray' => 
      array (
        'name' => 'compileWheresToArray',
        'parameters' => 
        array (
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 45,
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
 * Get an array of all the where clauses for the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return array
 */',
        'startLine' => 251,
        'endLine' => 256,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'concatenateWhereClauses' => 
      array (
        'name' => 'concatenateWhereClauses',
        'parameters' => 
        array (
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 265,
            'endLine' => 265,
            'startColumn' => 48,
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
            'startLine' => 265,
            'endLine' => 265,
            'startColumn' => 56,
            'endColumn' => 59,
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
 * Format the where clause statements into one string.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $sql
 * @return string
 */',
        'startLine' => 265,
        'endLine' => 270,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereRaw' => 
      array (
        'name' => 'whereRaw',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 279,
            'endLine' => 279,
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
 * Compile a raw where clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 279,
        'endLine' => 282,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereBasic' => 
      array (
        'name' => 'whereBasic',
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
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 51,
            'endColumn' => 56,
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
 * Compile a basic where clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 291,
        'endLine' => 298,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereBitwise' => 
      array (
        'name' => 'whereBitwise',
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
            'startLine' => 307,
            'endLine' => 307,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 307,
            'endLine' => 307,
            'startColumn' => 53,
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
 * Compile a bitwise operator where clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 307,
        'endLine' => 310,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereLike' => 
      array (
        'name' => 'whereLike',
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
            'startLine' => 319,
            'endLine' => 319,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 319,
            'endLine' => 319,
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
 * Compile a "where like" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 319,
        'endLine' => 328,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereIn' => 
      array (
        'name' => 'whereIn',
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
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 32,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 48,
            'endColumn' => 53,
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
 * Compile a "where in" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 337,
        'endLine' => 344,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereNotIn' => 
      array (
        'name' => 'whereNotIn',
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
            'startLine' => 353,
            'endLine' => 353,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 353,
            'endLine' => 353,
            'startColumn' => 51,
            'endColumn' => 56,
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
 * Compile a "where not in" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 353,
        'endLine' => 360,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereNotInRaw' => 
      array (
        'name' => 'whereNotInRaw',
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
            'startLine' => 371,
            'endLine' => 371,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 371,
            'endLine' => 371,
            'startColumn' => 54,
            'endColumn' => 59,
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
 * Compile a "where not in raw" clause.
 *
 * For safety, whereIntegerInRaw ensures this method is only used with integer values.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 371,
        'endLine' => 378,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereInRaw' => 
      array (
        'name' => 'whereInRaw',
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
            'startLine' => 389,
            'endLine' => 389,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 389,
            'endLine' => 389,
            'startColumn' => 51,
            'endColumn' => 56,
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
 * Compile a "where in raw" clause.
 *
 * For safety, whereIntegerInRaw ensures this method is only used with integer values.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 389,
        'endLine' => 396,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereNull' => 
      array (
        'name' => 'whereNull',
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
            'startLine' => 405,
            'endLine' => 405,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 405,
            'endLine' => 405,
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
 * Compile a "where null" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 405,
        'endLine' => 408,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereNotNull' => 
      array (
        'name' => 'whereNotNull',
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
            'startLine' => 417,
            'endLine' => 417,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
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
            'startColumn' => 53,
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
 * Compile a "where not null" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 417,
        'endLine' => 420,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereBetween' => 
      array (
        'name' => 'whereBetween',
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
            'startLine' => 429,
            'endLine' => 429,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 429,
            'endLine' => 429,
            'startColumn' => 53,
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
 * Compile a "between" where clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 429,
        'endLine' => 438,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereBetweenColumns' => 
      array (
        'name' => 'whereBetweenColumns',
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
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 44,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 60,
            'endColumn' => 65,
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
 * Compile a "between" where clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 447,
        'endLine' => 456,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereValueBetween' => 
      array (
        'name' => 'whereValueBetween',
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
            'startLine' => 465,
            'endLine' => 465,
            'startColumn' => 42,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 465,
            'endLine' => 465,
            'startColumn' => 58,
            'endColumn' => 63,
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
 * Compile a "value between" where clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 465,
        'endLine' => 474,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereDate' => 
      array (
        'name' => 'whereDate',
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
            'startLine' => 483,
            'endLine' => 483,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 483,
            'endLine' => 483,
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
 * Compile a "where date" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 483,
        'endLine' => 486,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereTime' => 
      array (
        'name' => 'whereTime',
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
            'startLine' => 495,
            'endLine' => 495,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 495,
            'endLine' => 495,
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
 * Compile a "where time" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 495,
        'endLine' => 498,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereDay' => 
      array (
        'name' => 'whereDay',
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
            'startLine' => 507,
            'endLine' => 507,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 507,
            'endLine' => 507,
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
 * Compile a "where day" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 507,
        'endLine' => 510,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereMonth' => 
      array (
        'name' => 'whereMonth',
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
            'startLine' => 519,
            'endLine' => 519,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 519,
            'endLine' => 519,
            'startColumn' => 51,
            'endColumn' => 56,
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
 * Compile a "where month" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 519,
        'endLine' => 522,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereYear' => 
      array (
        'name' => 'whereYear',
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
            'startLine' => 531,
            'endLine' => 531,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 531,
            'endLine' => 531,
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
 * Compile a "where year" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 531,
        'endLine' => 534,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'dateBasedWhere' => 
      array (
        'name' => 'dateBasedWhere',
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
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 39,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 46,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 62,
            'endColumn' => 67,
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
 * Compile a date based where clause.
 *
 * @param  string  $type
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 544,
        'endLine' => 549,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereColumn' => 
      array (
        'name' => 'whereColumn',
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
            'startLine' => 558,
            'endLine' => 558,
            'startColumn' => 36,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
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
            'startColumn' => 52,
            'endColumn' => 57,
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
 * Compile a where clause comparing two columns.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 558,
        'endLine' => 561,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereNested' => 
      array (
        'name' => 'whereNested',
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
            'startLine' => 570,
            'endLine' => 570,
            'startColumn' => 36,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 570,
            'endLine' => 570,
            'startColumn' => 52,
            'endColumn' => 57,
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
 * Compile a nested where clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 570,
        'endLine' => 578,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereSub' => 
      array (
        'name' => 'whereSub',
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
            'startLine' => 587,
            'endLine' => 587,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 587,
            'endLine' => 587,
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
 * Compile a where condition with a sub-select.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 587,
        'endLine' => 592,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereExists' => 
      array (
        'name' => 'whereExists',
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
            'startLine' => 601,
            'endLine' => 601,
            'startColumn' => 36,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 601,
            'endLine' => 601,
            'startColumn' => 52,
            'endColumn' => 57,
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
 * Compile a where exists clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 601,
        'endLine' => 604,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereNotExists' => 
      array (
        'name' => 'whereNotExists',
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
            'startLine' => 613,
            'endLine' => 613,
            'startColumn' => 39,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 613,
            'endLine' => 613,
            'startColumn' => 55,
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
 * Compile a where exists clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 613,
        'endLine' => 616,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereRowValues' => 
      array (
        'name' => 'whereRowValues',
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
            'startLine' => 625,
            'endLine' => 625,
            'startColumn' => 39,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 625,
            'endLine' => 625,
            'startColumn' => 55,
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
 * Compile a where row values condition.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 625,
        'endLine' => 632,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereJsonBoolean' => 
      array (
        'name' => 'whereJsonBoolean',
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
            'startLine' => 641,
            'endLine' => 641,
            'startColumn' => 41,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 641,
            'endLine' => 641,
            'startColumn' => 57,
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
 * Compile a "where JSON boolean" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 641,
        'endLine' => 650,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereJsonContains' => 
      array (
        'name' => 'whereJsonContains',
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
            'startLine' => 659,
            'endLine' => 659,
            'startColumn' => 42,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 659,
            'endLine' => 659,
            'startColumn' => 58,
            'endColumn' => 63,
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
 * Compile a "where JSON contains" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 659,
        'endLine' => 667,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileJsonContains' => 
      array (
        'name' => 'compileJsonContains',
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
            'startLine' => 678,
            'endLine' => 678,
            'startColumn' => 44,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 678,
            'endLine' => 678,
            'startColumn' => 53,
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
 * Compile a "JSON contains" statement into SQL.
 *
 * @param  string  $column
 * @param  string  $value
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 678,
        'endLine' => 681,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereJsonOverlaps' => 
      array (
        'name' => 'whereJsonOverlaps',
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
            'startLine' => 690,
            'endLine' => 690,
            'startColumn' => 42,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 690,
            'endLine' => 690,
            'startColumn' => 58,
            'endColumn' => 63,
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
 * Compile a "where JSON overlaps" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 690,
        'endLine' => 698,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileJsonOverlaps' => 
      array (
        'name' => 'compileJsonOverlaps',
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
            'startLine' => 709,
            'endLine' => 709,
            'startColumn' => 44,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 709,
            'endLine' => 709,
            'startColumn' => 53,
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
 * Compile a "JSON overlaps" statement into SQL.
 *
 * @param  string  $column
 * @param  string  $value
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 709,
        'endLine' => 712,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'prepareBindingForJsonContains' => 
      array (
        'name' => 'prepareBindingForJsonContains',
        'parameters' => 
        array (
          'binding' => 
          array (
            'name' => 'binding',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 720,
            'endLine' => 720,
            'startColumn' => 51,
            'endColumn' => 58,
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
 * Prepare the binding for a "JSON contains" statement.
 *
 * @param  mixed  $binding
 * @return string
 */',
        'startLine' => 720,
        'endLine' => 723,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereJsonContainsKey' => 
      array (
        'name' => 'whereJsonContainsKey',
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
            'startLine' => 732,
            'endLine' => 732,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 732,
            'endLine' => 732,
            'startColumn' => 61,
            'endColumn' => 66,
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
 * Compile a "where JSON contains key" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 732,
        'endLine' => 739,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileJsonContainsKey' => 
      array (
        'name' => 'compileJsonContainsKey',
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
            'startLine' => 749,
            'endLine' => 749,
            'startColumn' => 47,
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
 * Compile a "JSON contains key" statement into SQL.
 *
 * @param  string  $column
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 749,
        'endLine' => 752,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereJsonLength' => 
      array (
        'name' => 'whereJsonLength',
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
            'startLine' => 761,
            'endLine' => 761,
            'startColumn' => 40,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 761,
            'endLine' => 761,
            'startColumn' => 56,
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
        'docComment' => '/**
 * Compile a "where JSON length" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 761,
        'endLine' => 768,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileJsonLength' => 
      array (
        'name' => 'compileJsonLength',
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
            'startLine' => 780,
            'endLine' => 780,
            'startColumn' => 42,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'operator' => 
          array (
            'name' => 'operator',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 780,
            'endLine' => 780,
            'startColumn' => 51,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 780,
            'endLine' => 780,
            'startColumn' => 62,
            'endColumn' => 67,
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
 * Compile a "JSON length" statement into SQL.
 *
 * @param  string  $column
 * @param  string  $operator
 * @param  string  $value
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 780,
        'endLine' => 783,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileJsonValueCast' => 
      array (
        'name' => 'compileJsonValueCast',
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
            'startLine' => 791,
            'endLine' => 791,
            'startColumn' => 42,
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
 * Compile a "JSON value cast" statement into SQL.
 *
 * @param  string  $value
 * @return string
 */',
        'startLine' => 791,
        'endLine' => 794,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereFullText' => 
      array (
        'name' => 'whereFullText',
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
            'startLine' => 803,
            'endLine' => 803,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 803,
            'endLine' => 803,
            'startColumn' => 51,
            'endColumn' => 56,
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
 * Compile a "where fulltext" clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 803,
        'endLine' => 806,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'whereExpression' => 
      array (
        'name' => 'whereExpression',
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
            'startLine' => 815,
            'endLine' => 815,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 815,
            'endLine' => 815,
            'startColumn' => 53,
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
 * Compile a clause based on an expression.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $where
 * @return string
 */',
        'startLine' => 815,
        'endLine' => 818,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileGroups' => 
      array (
        'name' => 'compileGroups',
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
            'startLine' => 827,
            'endLine' => 827,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'groups' => 
          array (
            'name' => 'groups',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 827,
            'endLine' => 827,
            'startColumn' => 54,
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
 * Compile the "group by" portions of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $groups
 * @return string
 */',
        'startLine' => 827,
        'endLine' => 830,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileHavings' => 
      array (
        'name' => 'compileHavings',
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
            'startLine' => 838,
            'endLine' => 838,
            'startColumn' => 39,
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
 * Compile the "having" portions of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return string
 */',
        'startLine' => 838,
        'endLine' => 843,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileHaving' => 
      array (
        'name' => 'compileHaving',
        'parameters' => 
        array (
          'having' => 
          array (
            'name' => 'having',
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
            'startLine' => 851,
            'endLine' => 851,
            'startColumn' => 38,
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
 * Compile a single having clause.
 *
 * @param  array  $having
 * @return string
 */',
        'startLine' => 851,
        'endLine' => 866,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileBasicHaving' => 
      array (
        'name' => 'compileBasicHaving',
        'parameters' => 
        array (
          'having' => 
          array (
            'name' => 'having',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 874,
            'endLine' => 874,
            'startColumn' => 43,
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
 * Compile a basic having clause.
 *
 * @param  array  $having
 * @return string
 */',
        'startLine' => 874,
        'endLine' => 881,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileHavingBetween' => 
      array (
        'name' => 'compileHavingBetween',
        'parameters' => 
        array (
          'having' => 
          array (
            'name' => 'having',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 889,
            'endLine' => 889,
            'startColumn' => 45,
            'endColumn' => 51,
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
 * Compile a "between" having clause.
 *
 * @param  array  $having
 * @return string
 */',
        'startLine' => 889,
        'endLine' => 900,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileHavingNull' => 
      array (
        'name' => 'compileHavingNull',
        'parameters' => 
        array (
          'having' => 
          array (
            'name' => 'having',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 908,
            'endLine' => 908,
            'startColumn' => 42,
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
 * Compile a having null clause.
 *
 * @param  array  $having
 * @return string
 */',
        'startLine' => 908,
        'endLine' => 913,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileHavingNotNull' => 
      array (
        'name' => 'compileHavingNotNull',
        'parameters' => 
        array (
          'having' => 
          array (
            'name' => 'having',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 921,
            'endLine' => 921,
            'startColumn' => 45,
            'endColumn' => 51,
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
 * Compile a having not null clause.
 *
 * @param  array  $having
 * @return string
 */',
        'startLine' => 921,
        'endLine' => 926,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileHavingBit' => 
      array (
        'name' => 'compileHavingBit',
        'parameters' => 
        array (
          'having' => 
          array (
            'name' => 'having',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 934,
            'endLine' => 934,
            'startColumn' => 41,
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
 * Compile a having clause involving a bit operator.
 *
 * @param  array  $having
 * @return string
 */',
        'startLine' => 934,
        'endLine' => 941,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileHavingExpression' => 
      array (
        'name' => 'compileHavingExpression',
        'parameters' => 
        array (
          'having' => 
          array (
            'name' => 'having',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 949,
            'endLine' => 949,
            'startColumn' => 48,
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
 * Compile a having clause involving an expression.
 *
 * @param  array  $having
 * @return string
 */',
        'startLine' => 949,
        'endLine' => 952,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileNestedHavings' => 
      array (
        'name' => 'compileNestedHavings',
        'parameters' => 
        array (
          'having' => 
          array (
            'name' => 'having',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 960,
            'endLine' => 960,
            'startColumn' => 45,
            'endColumn' => 51,
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
 * Compile a nested having clause.
 *
 * @param  array  $having
 * @return string
 */',
        'startLine' => 960,
        'endLine' => 963,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileOrders' => 
      array (
        'name' => 'compileOrders',
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
            'startLine' => 972,
            'endLine' => 972,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'orders' => 
          array (
            'name' => 'orders',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 972,
            'endLine' => 972,
            'startColumn' => 54,
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
 * Compile the "order by" portions of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $orders
 * @return string
 */',
        'startLine' => 972,
        'endLine' => 979,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileOrdersToArray' => 
      array (
        'name' => 'compileOrdersToArray',
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
            'startLine' => 988,
            'endLine' => 988,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'orders' => 
          array (
            'name' => 'orders',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 988,
            'endLine' => 988,
            'startColumn' => 61,
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
 * Compile the query orders to an array.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $orders
 * @return array
 */',
        'startLine' => 988,
        'endLine' => 997,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileRandom' => 
      array (
        'name' => 'compileRandom',
        'parameters' => 
        array (
          'seed' => 
          array (
            'name' => 'seed',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1005,
            'endLine' => 1005,
            'startColumn' => 35,
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
 * Compile the random statement into SQL.
 *
 * @param  string|int  $seed
 * @return string
 */',
        'startLine' => 1005,
        'endLine' => 1008,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileLimit' => 
      array (
        'name' => 'compileLimit',
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
            'startLine' => 1017,
            'endLine' => 1017,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'limit' => 
          array (
            'name' => 'limit',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1017,
            'endLine' => 1017,
            'startColumn' => 53,
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
 * Compile the "limit" portions of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  int  $limit
 * @return string
 */',
        'startLine' => 1017,
        'endLine' => 1020,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileGroupLimit' => 
      array (
        'name' => 'compileGroupLimit',
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
            'startLine' => 1028,
            'endLine' => 1028,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * Compile a group limit clause.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return string
 */',
        'startLine' => 1028,
        'endLine' => 1066,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileRowNumber' => 
      array (
        'name' => 'compileRowNumber',
        'parameters' => 
        array (
          'partition' => 
          array (
            'name' => 'partition',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1075,
            'endLine' => 1075,
            'startColumn' => 41,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'orders' => 
          array (
            'name' => 'orders',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1075,
            'endLine' => 1075,
            'startColumn' => 53,
            'endColumn' => 59,
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
 * Compile a row number clause.
 *
 * @param  string  $partition
 * @param  string  $orders
 * @return string
 */',
        'startLine' => 1075,
        'endLine' => 1080,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileOffset' => 
      array (
        'name' => 'compileOffset',
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
            'startLine' => 1089,
            'endLine' => 1089,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'offset' => 
          array (
            'name' => 'offset',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1089,
            'endLine' => 1089,
            'startColumn' => 54,
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
 * Compile the "offset" portions of the query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  int  $offset
 * @return string
 */',
        'startLine' => 1089,
        'endLine' => 1092,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileUnions' => 
      array (
        'name' => 'compileUnions',
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
            'startLine' => 1100,
            'endLine' => 1100,
            'startColumn' => 38,
            'endColumn' => 51,
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
 * Compile the "union" queries attached to the main query.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return string
 */',
        'startLine' => 1100,
        'endLine' => 1121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileUnion' => 
      array (
        'name' => 'compileUnion',
        'parameters' => 
        array (
          'union' => 
          array (
            'name' => 'union',
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
            'startLine' => 1129,
            'endLine' => 1129,
            'startColumn' => 37,
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
 * Compile a single union statement.
 *
 * @param  array  $union
 * @return string
 */',
        'startLine' => 1129,
        'endLine' => 1134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'wrapUnion' => 
      array (
        'name' => 'wrapUnion',
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
            'startLine' => 1142,
            'endLine' => 1142,
            'startColumn' => 34,
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
 * Wrap a union subquery in parentheses.
 *
 * @param  string  $sql
 * @return string
 */',
        'startLine' => 1142,
        'endLine' => 1145,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileUnionAggregate' => 
      array (
        'name' => 'compileUnionAggregate',
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
            'startLine' => 1153,
            'endLine' => 1153,
            'startColumn' => 46,
            'endColumn' => 59,
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
 * Compile a union aggregate query into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return string
 */',
        'startLine' => 1153,
        'endLine' => 1160,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileExists' => 
      array (
        'name' => 'compileExists',
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
            'startLine' => 1168,
            'endLine' => 1168,
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
 * Compile an exists statement into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return string
 */',
        'startLine' => 1168,
        'endLine' => 1173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileInsert' => 
      array (
        'name' => 'compileInsert',
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
            'startLine' => 1182,
            'endLine' => 1182,
            'startColumn' => 35,
            'endColumn' => 48,
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
            'startLine' => 1182,
            'endLine' => 1182,
            'startColumn' => 51,
            'endColumn' => 63,
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
 * Compile an insert statement into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $values
 * @return string
 */',
        'startLine' => 1182,
        'endLine' => 1207,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileInsertOrIgnore' => 
      array (
        'name' => 'compileInsertOrIgnore',
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
            'startLine' => 1218,
            'endLine' => 1218,
            'startColumn' => 43,
            'endColumn' => 56,
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
            'startLine' => 1218,
            'endLine' => 1218,
            'startColumn' => 59,
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
 * Compile an insert ignore statement into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $values
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 1218,
        'endLine' => 1221,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileInsertGetId' => 
      array (
        'name' => 'compileInsertGetId',
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
            'startLine' => 1231,
            'endLine' => 1231,
            'startColumn' => 40,
            'endColumn' => 53,
            'parameterIndex' => 0,
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
            'startLine' => 1231,
            'endLine' => 1231,
            'startColumn' => 56,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'sequence' => 
          array (
            'name' => 'sequence',
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
            'startColumn' => 65,
            'endColumn' => 73,
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
 * Compile an insert and get ID statement into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $values
 * @param  string|null  $sequence
 * @return string
 */',
        'startLine' => 1231,
        'endLine' => 1234,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileInsertUsing' => 
      array (
        'name' => 'compileInsertUsing',
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
            'startLine' => 1244,
            'endLine' => 1244,
            'startColumn' => 40,
            'endColumn' => 53,
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
            'startLine' => 1244,
            'endLine' => 1244,
            'startColumn' => 56,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'sql' => 
          array (
            'name' => 'sql',
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
            'startLine' => 1244,
            'endLine' => 1244,
            'startColumn' => 72,
            'endColumn' => 82,
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
 * Compile an insert statement using a subquery into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $columns
 * @param  string  $sql
 * @return string
 */',
        'startLine' => 1244,
        'endLine' => 1253,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileInsertOrIgnoreUsing' => 
      array (
        'name' => 'compileInsertOrIgnoreUsing',
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
            'startLine' => 1265,
            'endLine' => 1265,
            'startColumn' => 48,
            'endColumn' => 61,
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
            'startLine' => 1265,
            'endLine' => 1265,
            'startColumn' => 64,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'sql' => 
          array (
            'name' => 'sql',
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
            'startLine' => 1265,
            'endLine' => 1265,
            'startColumn' => 80,
            'endColumn' => 90,
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
 * Compile an insert ignore statement using a subquery into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $columns
 * @param  string  $sql
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 1265,
        'endLine' => 1268,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileUpdate' => 
      array (
        'name' => 'compileUpdate',
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
            'startLine' => 1277,
            'endLine' => 1277,
            'startColumn' => 35,
            'endColumn' => 48,
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
            'startLine' => 1277,
            'endLine' => 1277,
            'startColumn' => 51,
            'endColumn' => 63,
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
 * Compile an update statement into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $values
 * @return string
 */',
        'startLine' => 1277,
        'endLine' => 1290,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileUpdateColumns' => 
      array (
        'name' => 'compileUpdateColumns',
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
            'startLine' => 1299,
            'endLine' => 1299,
            'startColumn' => 45,
            'endColumn' => 58,
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
            'startLine' => 1299,
            'endLine' => 1299,
            'startColumn' => 61,
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
 * Compile the columns for an update statement.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $values
 * @return string
 */',
        'startLine' => 1299,
        'endLine' => 1304,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileUpdateWithoutJoins' => 
      array (
        'name' => 'compileUpdateWithoutJoins',
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
            'startLine' => 1315,
            'endLine' => 1315,
            'startColumn' => 50,
            'endColumn' => 63,
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
            'startLine' => 1315,
            'endLine' => 1315,
            'startColumn' => 66,
            'endColumn' => 71,
            'parameterIndex' => 1,
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
            'startLine' => 1315,
            'endLine' => 1315,
            'startColumn' => 74,
            'endColumn' => 81,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1315,
            'endLine' => 1315,
            'startColumn' => 84,
            'endColumn' => 89,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compile an update statement without joins into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  string  $table
 * @param  string  $columns
 * @param  string  $where
 * @return string
 */',
        'startLine' => 1315,
        'endLine' => 1318,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileUpdateWithJoins' => 
      array (
        'name' => 'compileUpdateWithJoins',
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
            'startLine' => 1329,
            'endLine' => 1329,
            'startColumn' => 47,
            'endColumn' => 60,
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
            'startLine' => 1329,
            'endLine' => 1329,
            'startColumn' => 63,
            'endColumn' => 68,
            'parameterIndex' => 1,
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
            'startLine' => 1329,
            'endLine' => 1329,
            'startColumn' => 71,
            'endColumn' => 78,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1329,
            'endLine' => 1329,
            'startColumn' => 81,
            'endColumn' => 86,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compile an update statement with joins into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  string  $table
 * @param  string  $columns
 * @param  string  $where
 * @return string
 */',
        'startLine' => 1329,
        'endLine' => 1334,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileUpsert' => 
      array (
        'name' => 'compileUpsert',
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
            'startLine' => 1347,
            'endLine' => 1347,
            'startColumn' => 35,
            'endColumn' => 48,
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
            'startLine' => 1347,
            'endLine' => 1347,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'uniqueBy' => 
          array (
            'name' => 'uniqueBy',
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
            'startLine' => 1347,
            'endLine' => 1347,
            'startColumn' => 66,
            'endColumn' => 80,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'update' => 
          array (
            'name' => 'update',
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
            'startLine' => 1347,
            'endLine' => 1347,
            'startColumn' => 83,
            'endColumn' => 95,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compile an "upsert" statement into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  array  $values
 * @param  array  $uniqueBy
 * @param  array  $update
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 1347,
        'endLine' => 1350,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'prepareBindingsForUpdate' => 
      array (
        'name' => 'prepareBindingsForUpdate',
        'parameters' => 
        array (
          'bindings' => 
          array (
            'name' => 'bindings',
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
            'startLine' => 1359,
            'endLine' => 1359,
            'startColumn' => 46,
            'endColumn' => 60,
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
            'startLine' => 1359,
            'endLine' => 1359,
            'startColumn' => 63,
            'endColumn' => 75,
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
 * Prepare the bindings for an update statement.
 *
 * @param  array  $bindings
 * @param  array  $values
 * @return array
 */',
        'startLine' => 1359,
        'endLine' => 1368,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileDelete' => 
      array (
        'name' => 'compileDelete',
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
            'startLine' => 1376,
            'endLine' => 1376,
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
 * Compile a delete statement into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return string
 */',
        'startLine' => 1376,
        'endLine' => 1387,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileDeleteWithoutJoins' => 
      array (
        'name' => 'compileDeleteWithoutJoins',
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
            'startLine' => 1397,
            'endLine' => 1397,
            'startColumn' => 50,
            'endColumn' => 63,
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
            'startLine' => 1397,
            'endLine' => 1397,
            'startColumn' => 66,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1397,
            'endLine' => 1397,
            'startColumn' => 74,
            'endColumn' => 79,
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
 * Compile a delete statement without joins into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  string  $table
 * @param  string  $where
 * @return string
 */',
        'startLine' => 1397,
        'endLine' => 1400,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileDeleteWithJoins' => 
      array (
        'name' => 'compileDeleteWithJoins',
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
            'startLine' => 1410,
            'endLine' => 1410,
            'startColumn' => 47,
            'endColumn' => 60,
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
            'startLine' => 1410,
            'endLine' => 1410,
            'startColumn' => 63,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'where' => 
          array (
            'name' => 'where',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1410,
            'endLine' => 1410,
            'startColumn' => 71,
            'endColumn' => 76,
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
 * Compile a delete statement with joins into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  string  $table
 * @param  string  $where
 * @return string
 */',
        'startLine' => 1410,
        'endLine' => 1417,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'prepareBindingsForDelete' => 
      array (
        'name' => 'prepareBindingsForDelete',
        'parameters' => 
        array (
          'bindings' => 
          array (
            'name' => 'bindings',
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
            'startLine' => 1425,
            'endLine' => 1425,
            'startColumn' => 46,
            'endColumn' => 60,
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
 * Prepare the bindings for a delete statement.
 *
 * @param  array  $bindings
 * @return array
 */',
        'startLine' => 1425,
        'endLine' => 1430,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileTruncate' => 
      array (
        'name' => 'compileTruncate',
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
            'startLine' => 1438,
            'endLine' => 1438,
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
 * Compile a truncate table statement into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @return array
 */',
        'startLine' => 1438,
        'endLine' => 1441,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileLock' => 
      array (
        'name' => 'compileLock',
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
            'startLine' => 1450,
            'endLine' => 1450,
            'startColumn' => 36,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 1450,
            'endLine' => 1450,
            'startColumn' => 52,
            'endColumn' => 57,
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
 * Compile the lock into SQL.
 *
 * @param  \\Illuminate\\Database\\Query\\Builder  $query
 * @param  bool|string  $value
 * @return string
 */',
        'startLine' => 1450,
        'endLine' => 1453,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileThreadCount' => 
      array (
        'name' => 'compileThreadCount',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compile a query to get the number of open connections for a database.
 *
 * @return string|null
 */',
        'startLine' => 1460,
        'endLine' => 1463,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'supportsSavepoints' => 
      array (
        'name' => 'supportsSavepoints',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the grammar supports savepoints.
 *
 * @return bool
 */',
        'startLine' => 1470,
        'endLine' => 1473,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileSavepoint' => 
      array (
        'name' => 'compileSavepoint',
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
            'startLine' => 1481,
            'endLine' => 1481,
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
 * Compile the SQL statement to define a savepoint.
 *
 * @param  string  $name
 * @return string
 */',
        'startLine' => 1481,
        'endLine' => 1484,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'compileSavepointRollBack' => 
      array (
        'name' => 'compileSavepointRollBack',
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
            'startLine' => 1492,
            'endLine' => 1492,
            'startColumn' => 46,
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
 * Compile the SQL statement to execute a savepoint rollback.
 *
 * @param  string  $name
 * @return string
 */',
        'startLine' => 1492,
        'endLine' => 1495,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'wrapJsonBooleanSelector' => 
      array (
        'name' => 'wrapJsonBooleanSelector',
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
            'startLine' => 1503,
            'endLine' => 1503,
            'startColumn' => 48,
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
 * Wrap the given JSON selector for boolean values.
 *
 * @param  string  $value
 * @return string
 */',
        'startLine' => 1503,
        'endLine' => 1506,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'wrapJsonBooleanValue' => 
      array (
        'name' => 'wrapJsonBooleanValue',
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
            'startLine' => 1514,
            'endLine' => 1514,
            'startColumn' => 45,
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
 * Wrap the given JSON boolean value.
 *
 * @param  string  $value
 * @return string
 */',
        'startLine' => 1514,
        'endLine' => 1517,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'concatenate' => 
      array (
        'name' => 'concatenate',
        'parameters' => 
        array (
          'segments' => 
          array (
            'name' => 'segments',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1525,
            'endLine' => 1525,
            'startColumn' => 36,
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
 * Concatenate an array of segments, removing empties.
 *
 * @param  array  $segments
 * @return string
 */',
        'startLine' => 1525,
        'endLine' => 1530,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'removeLeadingBoolean' => 
      array (
        'name' => 'removeLeadingBoolean',
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
            'startLine' => 1538,
            'endLine' => 1538,
            'startColumn' => 45,
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
 * Remove the leading boolean from a statement.
 *
 * @param  string  $value
 * @return string
 */',
        'startLine' => 1538,
        'endLine' => 1541,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'substituteBindingsIntoRawSql' => 
      array (
        'name' => 'substituteBindingsIntoRawSql',
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
            'startLine' => 1550,
            'endLine' => 1550,
            'startColumn' => 50,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'bindings' => 
          array (
            'name' => 'bindings',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1550,
            'endLine' => 1550,
            'startColumn' => 56,
            'endColumn' => 64,
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
 * Substitute the given bindings into the given raw SQL query.
 *
 * @param  string  $sql
 * @param  array  $bindings
 * @return string
 */',
        'startLine' => 1550,
        'endLine' => 1579,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getOperators' => 
      array (
        'name' => 'getOperators',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the grammar specific operators.
 *
 * @return array
 */',
        'startLine' => 1586,
        'endLine' => 1589,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'aliasName' => NULL,
      ),
      'getBitwiseOperators' => 
      array (
        'name' => 'getBitwiseOperators',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the grammar specific bitwise operators.
 *
 * @return array
 */',
        'startLine' => 1596,
        'endLine' => 1599,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Query\\Grammars',
        'declaringClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'implementingClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
        'currentClassName' => 'Illuminate\\Database\\Query\\Grammars\\Grammar',
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