<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Bus/Queueable.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Bus\Queueable
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-7df8b51aab8bd3196229be1a8e398c2c2ec636ae1767ce499a64bfdbf5675c47-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Bus\\Queueable',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Bus/Queueable.php',
      ),
    ),
    'namespace' => 'Illuminate\\Bus',
    'name' => 'Illuminate\\Bus\\Queueable',
    'shortName' => 'Queueable',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 386,
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
      'connection' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'connection',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The name of the connection the job should be sent to.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'queue' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'queue',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The name of the queue the job should be sent to.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'messageGroup' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'messageGroup',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The job "group" the job should be sent to.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'deduplicator' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'deduplicator',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The job deduplicator callback the job should use to generate the deduplication ID.
 *
 * @var \\Laravel\\SerializableClosure\\SerializableClosure|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'delay' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'delay',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The number of seconds before the job should be made available.
 *
 * @var \\DateTimeInterface|\\DateInterval|array|int|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'afterCommit' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'afterCommit',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Indicates whether the job should be dispatched after all database transactions have committed.
 *
 * @var bool|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'middleware' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'middleware',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 109,
            'startFilePos' => 1388,
            'endTokenPos' => 110,
            'endFilePos' => 1389,
          ),
        ),
        'docComment' => '/**
 * The middleware the job should be dispatched through.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'chained' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'chained',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 121,
            'startFilePos' => 1515,
            'endTokenPos' => 122,
            'endFilePos' => 1516,
          ),
        ),
        'docComment' => '/**
 * The jobs that should run if this job is successful.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'chainConnection' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'chainConnection',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The name of the connection the chain should be sent to.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'chainQueue' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'chainQueue',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The name of the queue the chain should be sent to.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'chainCatchCallbacks' => 
      array (
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'name' => 'chainCatchCallbacks',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The callbacks to be executed on chain failure.
 *
 * @var array|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 32,
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
      'onConnection' => 
      array (
        'name' => 'onConnection',
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 34,
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
 * Set the desired connection for the job.
 *
 * @param  \\UnitEnum|string|null  $connection
 * @return $this
 */',
        'startLine' => 100,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'onQueue' => 
      array (
        'name' => 'onQueue',
        'parameters' => 
        array (
          'queue' => 
          array (
            'name' => 'queue',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 29,
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
 * Set the desired queue for the job.
 *
 * @param  \\UnitEnum|string|null  $queue
 * @return $this
 */',
        'startLine' => 113,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'onGroup' => 
      array (
        'name' => 'onGroup',
        'parameters' => 
        array (
          'group' => 
          array (
            'name' => 'group',
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
            'startColumn' => 29,
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
 * Set the desired job "group".
 *
 * This feature is only supported by some queues, such as Amazon SQS.
 *
 * @param  \\UnitEnum|string  $group
 * @return $this
 */',
        'startLine' => 128,
        'endLine' => 133,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'withDeduplicator' => 
      array (
        'name' => 'withDeduplicator',
        'parameters' => 
        array (
          'deduplicator' => 
          array (
            'name' => 'deduplicator',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 143,
            'endLine' => 143,
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
 * Set the desired job deduplicator callback.
 *
 * This feature is only supported by some queues, such as Amazon SQS FIFO.
 *
 * @param  callable|null  $deduplicator
 * @return $this
 */',
        'startLine' => 143,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'allOnConnection' => 
      array (
        'name' => 'allOnConnection',
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 37,
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
 * Set the desired connection for the chain.
 *
 * @param  \\UnitEnum|string|null  $connection
 * @return $this
 */',
        'startLine' => 158,
        'endLine' => 166,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'allOnQueue' => 
      array (
        'name' => 'allOnQueue',
        'parameters' => 
        array (
          'queue' => 
          array (
            'name' => 'queue',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 174,
            'endLine' => 174,
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
 * Set the desired queue for the chain.
 *
 * @param  \\UnitEnum|string|null  $queue
 * @return $this
 */',
        'startLine' => 174,
        'endLine' => 182,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'delay' => 
      array (
        'name' => 'delay',
        'parameters' => 
        array (
          'delay' => 
          array (
            'name' => 'delay',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 27,
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
 * Set the desired delay in seconds for the job.
 *
 * @param  \\DateTimeInterface|\\DateInterval|array|int|null  $delay
 * @return $this
 */',
        'startLine' => 190,
        'endLine' => 195,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'withoutDelay' => 
      array (
        'name' => 'withoutDelay',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the delay for the job to zero seconds.
 *
 * @return $this
 */',
        'startLine' => 202,
        'endLine' => 207,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'afterCommit' => 
      array (
        'name' => 'afterCommit',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the job should be dispatched after all database transactions have committed.
 *
 * @return $this
 */',
        'startLine' => 214,
        'endLine' => 219,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'beforeCommit' => 
      array (
        'name' => 'beforeCommit',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the job should not wait until database transactions have been committed before dispatching.
 *
 * @return $this
 */',
        'startLine' => 226,
        'endLine' => 231,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'through' => 
      array (
        'name' => 'through',
        'parameters' => 
        array (
          'middleware' => 
          array (
            'name' => 'middleware',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 239,
            'endLine' => 239,
            'startColumn' => 29,
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
 * Specify the middleware the job should be dispatched through.
 *
 * @param  array|object  $middleware
 * @return $this
 */',
        'startLine' => 239,
        'endLine' => 244,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'chain' => 
      array (
        'name' => 'chain',
        'parameters' => 
        array (
          'chain' => 
          array (
            'name' => 'chain',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 252,
            'endLine' => 252,
            'startColumn' => 27,
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
 * Set the jobs that should run if this job is successful.
 *
 * @param  array  $chain
 * @return $this
 */',
        'startLine' => 252,
        'endLine' => 259,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'prependToChain' => 
      array (
        'name' => 'prependToChain',
        'parameters' => 
        array (
          'job' => 
          array (
            'name' => 'job',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 267,
            'endLine' => 267,
            'startColumn' => 36,
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
 * Prepend a job to the current chain so that it is run after the currently running job.
 *
 * @param  mixed  $job
 * @return $this
 */',
        'startLine' => 267,
        'endLine' => 276,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'appendToChain' => 
      array (
        'name' => 'appendToChain',
        'parameters' => 
        array (
          'job' => 
          array (
            'name' => 'job',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 284,
            'endLine' => 284,
            'startColumn' => 35,
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
 * Append a job to the end of the current chain.
 *
 * @param  mixed  $job
 * @return $this
 */',
        'startLine' => 284,
        'endLine' => 293,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'serializeJob' => 
      array (
        'name' => 'serializeJob',
        'parameters' => 
        array (
          'job' => 
          array (
            'name' => 'job',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 37,
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
 * Serialize a job for queuing.
 *
 * @param  mixed  $job
 * @return string
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 303,
        'endLine' => 316,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'dispatchNextJobInChain' => 
      array (
        'name' => 'dispatchNextJobInChain',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Dispatch the next job on the chain.
 *
 * @return void
 */',
        'startLine' => 323,
        'endLine' => 337,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'invokeChainCatchCallbacks' => 
      array (
        'name' => 'invokeChainCatchCallbacks',
        'parameters' => 
        array (
          'e' => 
          array (
            'name' => 'e',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 345,
            'endLine' => 345,
            'startColumn' => 47,
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
 * Invoke all of the chain\'s failed job callbacks.
 *
 * @param  \\Throwable  $e
 * @return void
 */',
        'startLine' => 345,
        'endLine' => 350,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'assertHasChain' => 
      array (
        'name' => 'assertHasChain',
        'parameters' => 
        array (
          'expectedChain' => 
          array (
            'name' => 'expectedChain',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 358,
            'endLine' => 358,
            'startColumn' => 36,
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
 * Assert that the job has the given chain of jobs attached to it.
 *
 * @param  array  $expectedChain
 * @return void
 */',
        'startLine' => 358,
        'endLine' => 375,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
        'aliasName' => NULL,
      ),
      'assertDoesntHaveChain' => 
      array (
        'name' => 'assertDoesntHaveChain',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Assert that the job has no remaining chained jobs.
 *
 * @return void
 */',
        'startLine' => 382,
        'endLine' => 385,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Bus',
        'declaringClassName' => 'Illuminate\\Bus\\Queueable',
        'implementingClassName' => 'Illuminate\\Bus\\Queueable',
        'currentClassName' => 'Illuminate\\Bus\\Queueable',
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