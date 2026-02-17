<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/DatabaseTransactionsManager.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\DatabaseTransactionsManager
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-fa124514cd0ddbc88598205e376212a11f500955ea282d7998a73692d83c536d-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/DatabaseTransactionsManager.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database',
    'name' => 'Illuminate\\Database\\DatabaseTransactionsManager',
    'shortName' => 'DatabaseTransactionsManager',
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
    'endLine' => 267,
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
      'committedTransactions' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'name' => 'committedTransactions',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * All of the committed transactions.
 *
 * @var \\Illuminate\\Support\\Collection<int, \\Illuminate\\Database\\DatabaseTransactionRecord>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pendingTransactions' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'name' => 'pendingTransactions',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * All of the pending transactions.
 *
 * @var \\Illuminate\\Support\\Collection<int, \\Illuminate\\Database\\DatabaseTransactionRecord>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'currentTransaction' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'name' => 'currentTransaction',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 40,
            'startFilePos' => 616,
            'endTokenPos' => 41,
            'endFilePos' => 617,
          ),
        ),
        'docComment' => '/**
 * The current transaction.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 39,
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
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new database transactions manager instance.
 */',
        'startLine' => 33,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'begin' => 
      array (
        'name' => 'begin',
        'parameters' => 
        array (
          'connection' => 
          array (
            'name' => 'connection',
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
            'startColumn' => 27,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'level' => 
          array (
            'name' => 'level',
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
            'startColumn' => 40,
            'endColumn' => 45,
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
 * Start a new database transaction.
 *
 * @param  string  $connection
 * @param  int  $level
 * @return void
 */',
        'startLine' => 46,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'commit' => 
      array (
        'name' => 'commit',
        'parameters' => 
        array (
          'connection' => 
          array (
            'name' => 'connection',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'levelBeingCommitted' => 
          array (
            'name' => 'levelBeingCommitted',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 41,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'newTransactionLevel' => 
          array (
            'name' => 'newTransactionLevel',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 63,
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
 * Commit the root database transaction and execute callbacks.
 *
 * @param  string  $connection
 * @param  int  $levelBeingCommitted
 * @param  int  $newTransactionLevel
 * @return array
 */',
        'startLine' => 67,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'stageTransactions' => 
      array (
        'name' => 'stageTransactions',
        'parameters' => 
        array (
          'connection' => 
          array (
            'name' => 'connection',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 39,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'levelBeingCommitted' => 
          array (
            'name' => 'levelBeingCommitted',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 52,
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
 * Move relevant pending transactions to a committed state.
 *
 * @param  string  $connection
 * @param  int  $levelBeingCommitted
 * @return void
 */',
        'startLine' => 106,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'rollback' => 
      array (
        'name' => 'rollback',
        'parameters' => 
        array (
          'connection' => 
          array (
            'name' => 'connection',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 30,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'newTransactionLevel' => 
          array (
            'name' => 'newTransactionLevel',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 43,
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
 * Rollback the active database transaction.
 *
 * @param  string  $connection
 * @param  int  $newTransactionLevel
 * @return void
 */',
        'startLine' => 128,
        'endLine' => 151,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'removeAllTransactionsForConnection' => 
      array (
        'name' => 'removeAllTransactionsForConnection',
        'parameters' => 
        array (
          'connection' => 
          array (
            'name' => 'connection',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 59,
            'endColumn' => 69,
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
 * Remove all pending, completed, and current transactions for the given connection name.
 *
 * @param  string  $connection
 * @return void
 */',
        'startLine' => 159,
        'endLine' => 176,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'removeCommittedTransactionsThatAreChildrenOf' => 
      array (
        'name' => 'removeCommittedTransactionsThatAreChildrenOf',
        'parameters' => 
        array (
          'transaction' => 
          array (
            'name' => 'transaction',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\DatabaseTransactionRecord',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 184,
            'endLine' => 184,
            'startColumn' => 69,
            'endColumn' => 106,
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
 * Remove all transactions that are children of the given transaction.
 *
 * @param  \\Illuminate\\Database\\DatabaseTransactionRecord  $transaction
 * @return void
 */',
        'startLine' => 184,
        'endLine' => 197,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'addCallback' => 
      array (
        'name' => 'addCallback',
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
            'startLine' => 205,
            'endLine' => 205,
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
 * Register a transaction callback.
 *
 * @param  callable  $callback
 * @return void
 */',
        'startLine' => 205,
        'endLine' => 212,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'addCallbackForRollback' => 
      array (
        'name' => 'addCallbackForRollback',
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
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 44,
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
 * Register a callback for transaction rollback.
 *
 * @param  callable  $callback
 * @return void
 */',
        'startLine' => 220,
        'endLine' => 225,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'callbackApplicableTransactions' => 
      array (
        'name' => 'callbackApplicableTransactions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the transactions that are applicable to callbacks.
 *
 * @return \\Illuminate\\Support\\Collection<int, \\Illuminate\\Database\\DatabaseTransactionRecord>
 */',
        'startLine' => 232,
        'endLine' => 235,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'afterCommitCallbacksShouldBeExecuted' => 
      array (
        'name' => 'afterCommitCallbacksShouldBeExecuted',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 243,
            'endLine' => 243,
            'startColumn' => 58,
            'endColumn' => 63,
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
 * Determine if after commit callbacks should be executed for the given transaction level.
 *
 * @param  int  $level
 * @return bool
 */',
        'startLine' => 243,
        'endLine' => 246,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'getPendingTransactions' => 
      array (
        'name' => 'getPendingTransactions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all of the pending transactions.
 *
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 253,
        'endLine' => 256,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'aliasName' => NULL,
      ),
      'getCommittedTransactions' => 
      array (
        'name' => 'getCommittedTransactions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all of the committed transactions.
 *
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 263,
        'endLine' => 266,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database',
        'declaringClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'implementingClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
        'currentClassName' => 'Illuminate\\Database\\DatabaseTransactionsManager',
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