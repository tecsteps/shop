<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Concerns/ManagesTransactions.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Concerns\ManagesTransactions
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-ef9ba5ef58ac0ec1180c1116b46fd86cfd71cfa6d336fb728cc70e3c82d028f1-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Concerns/ManagesTransactions.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Concerns',
    'name' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
    'shortName' => 'ManagesTransactions',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @mixin \\Illuminate\\Database\\Connection
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 373,
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
      'transaction' => 
      array (
        'name' => 'transaction',
        'parameters' => 
        array (
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 33,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attempts' => 
          array (
            'name' => 'attempts',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 26,
                'endLine' => 26,
                'startTokenPos' => 52,
                'startFilePos' => 534,
                'endTokenPos' => 52,
                'endFilePos' => 534,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 52,
            'endColumn' => 64,
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
 * @template TReturn of mixed
 *
 * Execute a Closure within a transaction.
 *
 * @param  (\\Closure(static): TReturn)  $callback
 * @param  int  $attempts
 * @return TReturn
 *
 * @throws \\Throwable
 */',
        'startLine' => 26,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'handleTransactionException' => 
      array (
        'name' => 'handleTransactionException',
        'parameters' => 
        array (
          'e' => 
          array (
            'name' => 'e',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 51,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'currentAttempt' => 
          array (
            'name' => 'currentAttempt',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 65,
            'endColumn' => 79,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'maxAttempts' => 
          array (
            'name' => 'maxAttempts',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 82,
            'endColumn' => 93,
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
 * Handle an exception encountered when running a transacted statement.
 *
 * @param  \\Throwable  $e
 * @param  int  $currentAttempt
 * @param  int  $maxAttempts
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 88,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'beginTransaction' => 
      array (
        'name' => 'beginTransaction',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Start a new database transaction.
 *
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 124,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'createTransaction' => 
      array (
        'name' => 'createTransaction',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a transaction within the database.
 *
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 148,
        'endLine' => 161,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'createSavepoint' => 
      array (
        'name' => 'createSavepoint',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a save point within the database.
 *
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 170,
        'endLine' => 175,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'handleBeginTransactionException' => 
      array (
        'name' => 'handleBeginTransactionException',
        'parameters' => 
        array (
          'e' => 
          array (
            'name' => 'e',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 185,
            'endLine' => 185,
            'startColumn' => 56,
            'endColumn' => 67,
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
 * Handle an exception from a transaction beginning.
 *
 * @param  \\Throwable  $e
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 185,
        'endLine' => 194,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'commit' => 
      array (
        'name' => 'commit',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Commit the active database transaction.
 *
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 203,
        'endLine' => 220,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'handleCommitTransactionException' => 
      array (
        'name' => 'handleCommitTransactionException',
        'parameters' => 
        array (
          'e' => 
          array (
            'name' => 'e',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 57,
            'endColumn' => 68,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'currentAttempt' => 
          array (
            'name' => 'currentAttempt',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 71,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'maxAttempts' => 
          array (
            'name' => 'maxAttempts',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 88,
            'endColumn' => 99,
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
 * Handle an exception encountered when committing a transaction.
 *
 * @param  \\Throwable  $e
 * @param  int  $currentAttempt
 * @param  int  $maxAttempts
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 232,
        'endLine' => 245,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'rollBack' => 
      array (
        'name' => 'rollBack',
        'parameters' => 
        array (
          'toLevel' => 
          array (
            'name' => 'toLevel',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 255,
                'endLine' => 255,
                'startTokenPos' => 966,
                'startFilePos' => 7009,
                'endTokenPos' => 966,
                'endFilePos' => 7012,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 255,
            'endLine' => 255,
            'startColumn' => 30,
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
        'docComment' => '/**
 * Rollback the active database transaction.
 *
 * @param  int|null  $toLevel
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 255,
        'endLine' => 284,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'performRollBack' => 
      array (
        'name' => 'performRollBack',
        'parameters' => 
        array (
          'toLevel' => 
          array (
            'name' => 'toLevel',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 294,
            'endLine' => 294,
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
 * Perform a rollback within the database.
 *
 * @param  int  $toLevel
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 294,
        'endLine' => 307,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'handleRollBackException' => 
      array (
        'name' => 'handleRollBackException',
        'parameters' => 
        array (
          'e' => 
          array (
            'name' => 'e',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Throwable',
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
            'startColumn' => 48,
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
 * Handle an exception from a rollback.
 *
 * @param  \\Throwable  $e
 * @return void
 *
 * @throws \\Throwable
 */',
        'startLine' => 317,
        'endLine' => 328,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'transactionLevel' => 
      array (
        'name' => 'transactionLevel',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the number of active transactions.
 *
 * @return int
 */',
        'startLine' => 335,
        'endLine' => 338,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'afterCommit' => 
      array (
        'name' => 'afterCommit',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
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
 * Execute the callback after a transaction commits.
 *
 * @param  callable  $callback
 * @return void
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 348,
        'endLine' => 355,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'aliasName' => NULL,
      ),
      'afterRollBack' => 
      array (
        'name' => 'afterRollBack',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 365,
            'endLine' => 365,
            'startColumn' => 35,
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
 * Execute the callback after a transaction rolls back.
 *
 * @param  callable  $callback
 * @return void
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 365,
        'endLine' => 372,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'implementingClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
        'currentClassName' => 'Illuminate\\Database\\Concerns\\ManagesTransactions',
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