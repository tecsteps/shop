<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Eloquent/Relations/Concerns/InteractsWithPivotTable.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-1f8ca78db3925ecff25f0a7128a48b70a9943b9e79548daf2f22698b934a4289-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Eloquent/Relations/Concerns/InteractsWithPivotTable.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
    'name' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
    'shortName' => 'InteractsWithPivotTable',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 703,
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
      'toggle' => 
      array (
        'name' => 'toggle',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 28,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'touch' => 
          array (
            'name' => 'touch',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 22,
                'endLine' => 22,
                'startTokenPos' => 61,
                'startFilePos' => 602,
                'endTokenPos' => 61,
                'endFilePos' => 605,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
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
 * Toggles a model (or models) from the parent.
 *
 * Each existing model is detached, and non existing ones are attached.
 *
 * @param  mixed  $ids
 * @param  bool  $touch
 * @return array
 */',
        'startLine' => 22,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'syncWithoutDetaching' => 
      array (
        'name' => 'syncWithoutDetaching',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 42,
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
 * Sync the intermediate tables with a list of IDs without detaching.
 *
 * @param  \\Illuminate\\Support\\Collection|\\Illuminate\\Database\\Eloquent\\Model|array|int|string  $ids
 * @return array{attached: array, detached: array, updated: array}
 */',
        'startLine' => 72,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'sync' => 
      array (
        'name' => 'sync',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 26,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'detaching' => 
          array (
            'name' => 'detaching',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 84,
                'endLine' => 84,
                'startTokenPos' => 355,
                'startFilePos' => 2975,
                'endTokenPos' => 355,
                'endFilePos' => 2978,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 32,
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
 * Sync the intermediate tables with a list of IDs or collection of models.
 *
 * @param  \\Illuminate\\Support\\Collection|\\Illuminate\\Database\\Eloquent\\Model|array|int|string  $ids
 * @param  bool  $detaching
 * @return array{attached: array, detached: array, updated: array}
 */',
        'startLine' => 84,
        'endLine' => 132,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'syncWithPivotValues' => 
      array (
        'name' => 'syncWithPivotValues',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 41,
            'endColumn' => 44,
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 47,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'detaching' => 
          array (
            'name' => 'detaching',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 142,
                'endLine' => 142,
                'startTokenPos' => 648,
                'startFilePos' => 5397,
                'endTokenPos' => 648,
                'endFilePos' => 5400,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 62,
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
 * Sync the intermediate tables with a list of IDs or collection of models with the given pivot values.
 *
 * @param  \\Illuminate\\Support\\Collection|\\Illuminate\\Database\\Eloquent\\Model|array|int|string  $ids
 * @param  array  $values
 * @param  bool  $detaching
 * @return array{attached: array, detached: array, updated: array}
 */',
        'startLine' => 142,
        'endLine' => 147,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'formatRecordsList' => 
      array (
        'name' => 'formatRecordsList',
        'parameters' => 
        array (
          'records' => 
          array (
            'name' => 'records',
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
            'startLine' => 155,
            'endLine' => 155,
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
 * Format the sync / toggle record list so that it is keyed by ID.
 *
 * @param  array  $records
 * @return array
 */',
        'startLine' => 155,
        'endLine' => 168,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'attachNew' => 
      array (
        'name' => 'attachNew',
        'parameters' => 
        array (
          'records' => 
          array (
            'name' => 'records',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'current' => 
          array (
            'name' => 'current',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 50,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'touch' => 
          array (
            'name' => 'touch',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 178,
                'endLine' => 178,
                'startTokenPos' => 848,
                'startFilePos' => 6449,
                'endTokenPos' => 848,
                'endFilePos' => 6452,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
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
 * Attach all of the records that aren\'t in the given current records.
 *
 * @param  array  $records
 * @param  array  $current
 * @param  bool  $touch
 * @return array
 */',
        'startLine' => 178,
        'endLine' => 202,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'updateExistingPivot' => 
      array (
        'name' => 'updateExistingPivot',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 41,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
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
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 46,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'touch' => 
          array (
            'name' => 'touch',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 212,
                'endLine' => 212,
                'startTokenPos' => 1030,
                'startFilePos' => 7789,
                'endTokenPos' => 1030,
                'endFilePos' => 7792,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 65,
            'endColumn' => 77,
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
 * Update an existing pivot record on the table.
 *
 * @param  mixed  $id
 * @param  array  $attributes
 * @param  bool  $touch
 * @return int
 */',
        'startLine' => 212,
        'endLine' => 231,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'updateExistingPivotUsingCustomClass' => 
      array (
        'name' => 'updateExistingPivotUsingCustomClass',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 60,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 65,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'touch' => 
          array (
            'name' => 'touch',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 84,
            'endColumn' => 89,
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
 * Update an existing pivot record on the table via a custom class.
 *
 * @param  mixed  $id
 * @param  array  $attributes
 * @param  bool  $touch
 * @return int
 */',
        'startLine' => 241,
        'endLine' => 256,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'attach' => 
      array (
        'name' => 'attach',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 28,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 266,
                'endLine' => 266,
                'startTokenPos' => 1266,
                'startFilePos' => 9142,
                'endTokenPos' => 1267,
                'endFilePos' => 9143,
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
            'startLine' => 266,
            'endLine' => 266,
            'startColumn' => 34,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'touch' => 
          array (
            'name' => 'touch',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 266,
                'endLine' => 266,
                'startTokenPos' => 1274,
                'startFilePos' => 9155,
                'endTokenPos' => 1274,
                'endFilePos' => 9158,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 266,
            'endLine' => 266,
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
 * Attach a model to the parent.
 *
 * @param  mixed  $ids
 * @param  array  $attributes
 * @param  bool  $touch
 * @return void
 */',
        'startLine' => 266,
        'endLine' => 282,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'attachUsingCustomClass' => 
      array (
        'name' => 'attachUsingCustomClass',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
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
            'startColumn' => 47,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
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
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 53,
            'endColumn' => 69,
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
 * Attach a model to the parent using a custom class.
 *
 * @param  mixed  $ids
 * @param  array  $attributes
 * @return void
 */',
        'startLine' => 291,
        'endLine' => 300,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'formatAttachRecords' => 
      array (
        'name' => 'formatAttachRecords',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 44,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
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
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 50,
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
 * Create an array of records to insert into the pivot table.
 *
 * @param  array  $ids
 * @param  array  $attributes
 * @return array
 */',
        'startLine' => 309,
        'endLine' => 326,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'formatAttachRecord' => 
      array (
        'name' => 'formatAttachRecord',
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
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 43,
            'endColumn' => 46,
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
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 49,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
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
            'startColumn' => 57,
            'endColumn' => 67,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'hasTimestamps' => 
          array (
            'name' => 'hasTimestamps',
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
            'startColumn' => 70,
            'endColumn' => 83,
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
 * Create a full attachment record payload.
 *
 * @param  int  $key
 * @param  mixed  $value
 * @param  array  $attributes
 * @param  bool  $hasTimestamps
 * @return array
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
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'extractAttachIdAndAttributes' => 
      array (
        'name' => 'extractAttachIdAndAttributes',
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
            'startLine' => 354,
            'endLine' => 354,
            'startColumn' => 53,
            'endColumn' => 56,
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
            'startLine' => 354,
            'endLine' => 354,
            'startColumn' => 59,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
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
            'startLine' => 354,
            'endLine' => 354,
            'startColumn' => 67,
            'endColumn' => 83,
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
 * Get the attach record ID and extra attributes.
 *
 * @param  mixed  $key
 * @param  mixed  $value
 * @param  array  $attributes
 * @return array
 */',
        'startLine' => 354,
        'endLine' => 359,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'baseAttachRecord' => 
      array (
        'name' => 'baseAttachRecord',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 368,
            'endLine' => 368,
            'startColumn' => 41,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'timed' => 
          array (
            'name' => 'timed',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 368,
            'endLine' => 368,
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
 * Create a new pivot attachment record.
 *
 * @param  int  $id
 * @param  bool  $timed
 * @return array
 */',
        'startLine' => 368,
        'endLine' => 386,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'addTimestampsToAttachment' => 
      array (
        'name' => 'addTimestampsToAttachment',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 395,
            'endLine' => 395,
            'startColumn' => 50,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'exists' => 
          array (
            'name' => 'exists',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 395,
                'endLine' => 395,
                'startTokenPos' => 1804,
                'startFilePos' => 13107,
                'endTokenPos' => 1804,
                'endFilePos' => 13111,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 395,
            'endLine' => 395,
            'startColumn' => 65,
            'endColumn' => 79,
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
 * Set the creation and update timestamps on an attach record.
 *
 * @param  array  $record
 * @param  bool  $exists
 * @return array
 */',
        'startLine' => 395,
        'endLine' => 414,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'hasPivotColumn' => 
      array (
        'name' => 'hasPivotColumn',
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
            'startLine' => 422,
            'endLine' => 422,
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
 * Determine whether the given column is defined as a pivot column.
 *
 * @param  string  $column
 * @return bool
 */',
        'startLine' => 422,
        'endLine' => 425,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'detach' => 
      array (
        'name' => 'detach',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 434,
                'endLine' => 434,
                'startTokenPos' => 1976,
                'startFilePos' => 14024,
                'endTokenPos' => 1976,
                'endFilePos' => 14027,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 434,
            'endLine' => 434,
            'startColumn' => 28,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'touch' => 
          array (
            'name' => 'touch',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 434,
                'endLine' => 434,
                'startTokenPos' => 1983,
                'startFilePos' => 14039,
                'endTokenPos' => 1983,
                'endFilePos' => 14042,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 434,
            'endLine' => 434,
            'startColumn' => 41,
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
 * Detach models from the relationship.
 *
 * @param  mixed  $ids
 * @param  bool  $touch
 * @return int
 */',
        'startLine' => 434,
        'endLine' => 465,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'detachUsingCustomClass' => 
      array (
        'name' => 'detachUsingCustomClass',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 473,
            'endLine' => 473,
            'startColumn' => 47,
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
 * Detach models from the relationship using a custom class.
 *
 * @param  mixed  $ids
 * @return int
 */',
        'startLine' => 473,
        'endLine' => 484,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'getCurrentlyAttachedPivots' => 
      array (
        'name' => 'getCurrentlyAttachedPivots',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the pivot models that are currently attached.
 *
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 491,
        'endLine' => 494,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'getCurrentlyAttachedPivotsForIds' => 
      array (
        'name' => 'getCurrentlyAttachedPivotsForIds',
        'parameters' => 
        array (
          'ids' => 
          array (
            'name' => 'ids',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 502,
                'endLine' => 502,
                'startTokenPos' => 2237,
                'startFilePos' => 16027,
                'endTokenPos' => 2237,
                'endFilePos' => 16030,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 502,
            'endLine' => 502,
            'startColumn' => 57,
            'endColumn' => 67,
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
 * Get the pivot models that are currently attached, filtered by related model keys.
 *
 * @param  mixed  $ids
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 502,
        'endLine' => 518,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'newPivot' => 
      array (
        'name' => 'newPivot',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 527,
                'endLine' => 527,
                'startTokenPos' => 2398,
                'startFilePos' => 16894,
                'endTokenPos' => 2399,
                'endFilePos' => 16895,
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
            'startLine' => 527,
            'endLine' => 527,
            'startColumn' => 30,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'exists' => 
          array (
            'name' => 'exists',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 527,
                'endLine' => 527,
                'startTokenPos' => 2406,
                'startFilePos' => 16908,
                'endTokenPos' => 2406,
                'endFilePos' => 16912,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 527,
            'endLine' => 527,
            'startColumn' => 54,
            'endColumn' => 68,
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
 * Create a new pivot model instance.
 *
 * @param  array  $attributes
 * @param  bool  $exists
 * @return \\Illuminate\\Database\\Eloquent\\Relations\\Pivot
 */',
        'startLine' => 527,
        'endLine' => 538,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'newExistingPivot' => 
      array (
        'name' => 'newExistingPivot',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 546,
                'endLine' => 546,
                'startTokenPos' => 2511,
                'startFilePos' => 17531,
                'endTokenPos' => 2512,
                'endFilePos' => 17532,
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
            'startLine' => 546,
            'endLine' => 546,
            'startColumn' => 38,
            'endColumn' => 59,
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
 * Create a new existing pivot model instance.
 *
 * @param  array  $attributes
 * @return \\Illuminate\\Database\\Eloquent\\Relations\\Pivot
 */',
        'startLine' => 546,
        'endLine' => 549,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'newPivotStatement' => 
      array (
        'name' => 'newPivotStatement',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get a new plain query builder for the pivot table.
 *
 * @return \\Illuminate\\Database\\Query\\Builder
 */',
        'startLine' => 556,
        'endLine' => 559,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'newPivotStatementForId' => 
      array (
        'name' => 'newPivotStatementForId',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 44,
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
 * Get a new pivot statement for a given "other" ID.
 *
 * @param  mixed  $id
 * @return \\Illuminate\\Database\\Query\\Builder
 */',
        'startLine' => 567,
        'endLine' => 570,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'newPivotQuery' => 
      array (
        'name' => 'newPivotQuery',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new query builder for the pivot table.
 *
 * @return \\Illuminate\\Database\\Query\\Builder
 */',
        'startLine' => 577,
        'endLine' => 594,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'withPivot' => 
      array (
        'name' => 'withPivot',
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
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 31,
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
 * Set the columns on the pivot table to retrieve.
 *
 * @param  mixed  $columns
 * @return $this
 */',
        'startLine' => 602,
        'endLine' => 609,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'parseIds' => 
      array (
        'name' => 'parseIds',
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
            'startLine' => 617,
            'endLine' => 617,
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
 * Get all of the IDs from the given mixed value.
 *
 * @param  mixed  $value
 * @return array
 */',
        'startLine' => 617,
        'endLine' => 634,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'parseId' => 
      array (
        'name' => 'parseId',
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
            'startLine' => 642,
            'endLine' => 642,
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
 * Get the ID from the given mixed value.
 *
 * @param  mixed  $value
 * @return mixed
 */',
        'startLine' => 642,
        'endLine' => 645,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'castKeys' => 
      array (
        'name' => 'castKeys',
        'parameters' => 
        array (
          'keys' => 
          array (
            'name' => 'keys',
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
            'startLine' => 653,
            'endLine' => 653,
            'startColumn' => 33,
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
 * Cast the given keys to integers if they are numeric and string otherwise.
 *
 * @param  array  $keys
 * @return array
 */',
        'startLine' => 653,
        'endLine' => 658,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'castKey' => 
      array (
        'name' => 'castKey',
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
            'startLine' => 666,
            'endLine' => 666,
            'startColumn' => 32,
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
 * Cast the given key to convert to primary key type.
 *
 * @param  mixed  $key
 * @return mixed
 */',
        'startLine' => 666,
        'endLine' => 672,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'castAttributes' => 
      array (
        'name' => 'castAttributes',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 680,
            'endLine' => 680,
            'startColumn' => 39,
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
 * Cast the given pivot attributes.
 *
 * @param  array  $attributes
 * @return array
 */',
        'startLine' => 680,
        'endLine' => 685,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'aliasName' => NULL,
      ),
      'getTypeSwapValue' => 
      array (
        'name' => 'getTypeSwapValue',
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
            'startLine' => 694,
            'endLine' => 694,
            'startColumn' => 41,
            'endColumn' => 45,
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
            'startLine' => 694,
            'endLine' => 694,
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
 * Converts a given value to a given type value.
 *
 * @param  string  $type
 * @param  mixed  $value
 * @return mixed
 */',
        'startLine' => 694,
        'endLine' => 702,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns',
        'declaringClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'implementingClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
        'currentClassName' => 'Illuminate\\Database\\Eloquent\\Relations\\Concerns\\InteractsWithPivotTable',
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