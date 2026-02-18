<?php declare(strict_types = 1);

// ftm-/Users/fabianwesner/Herd/shop/app/Models/Cart.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v4-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      '07ea95e3a56b5b05a712eef0ce0c5719' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'cartstatus' => 'App\\Enums\\CartStatus',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '7cb28b0863cd7805f7966ca945069541' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models\\Concerns',
         'uses' => 
        array (
          'storescope' => 'App\\Models\\Scopes\\StoreScope',
          'store' => 'App\\Models\\Store',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'App\\Models\\Concerns\\BelongsToStore',
         'traitData' => 
        array (
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Cart.php',
          1 => 'App\\Models\\Cart',
          2 => 'App\\Models\\Concerns\\BelongsToStore',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\CartFactory> */',
        ),
      )),
      '21f8ef31fe3d69c5cf24a0d4e45da6d6' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models\\Concerns',
         'uses' => 
        array (
          'storescope' => 'App\\Models\\Scopes\\StoreScope',
          'store' => 'App\\Models\\Store',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'bootBelongsToStore',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'App\\Models\\Concerns\\BelongsToStore',
         'traitData' => 
        array (
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Cart.php',
          1 => 'App\\Models\\Cart',
          2 => 'App\\Models\\Concerns\\BelongsToStore',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\CartFactory> */',
        ),
      )),
      '9f563d2ee6cc6fb438ad37b524077298' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models\\Concerns',
         'uses' => 
        array (
          'storescope' => 'App\\Models\\Scopes\\StoreScope',
          'store' => 'App\\Models\\Store',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'store',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'App\\Models\\Concerns\\BelongsToStore',
         'traitData' => 
        array (
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Cart.php',
          1 => 'App\\Models\\Cart',
          2 => 'App\\Models\\Concerns\\BelongsToStore',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\CartFactory> */',
        ),
      )),
      'bebe524fafdfac757433e8126ad28411' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
         'uses' => 
        array (
          'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
          'TFactory' => 
          array (
            0 => '@template',
            1 => 
            \PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode::__set_state(array(
               'name' => 'TFactory',
               'bound' => 
              \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode::__set_state(array(
                 'name' => '\\Illuminate\\Database\\Eloquent\\Factories\\Factory',
                 'attributes' => 
                array (
                  'startLine' => 2,
                  'endLine' => 2,
                ),
              )),
               'default' => NULL,
               'lowerBound' => NULL,
               'description' => '',
               'attributes' => 
              array (
                'startLine' => 2,
                'endLine' => 2,
              ),
            )),
          ),
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
         'traitData' => 
        array (
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Cart.php',
          1 => 'App\\Models\\Cart',
          2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\CartFactory> */',
        ),
      )),
      '5fc67c027ff07397651176ee94633287' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
         'uses' => 
        array (
          'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'factory',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
           'uses' => 
          array (
            'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
          ),
           'className' => 'App\\Models\\Cart',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
            'TFactory' => 
            array (
              0 => '@template',
              1 => 
              \PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode::__set_state(array(
                 'name' => 'TFactory',
                 'bound' => 
                \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode::__set_state(array(
                   'name' => '\\Illuminate\\Database\\Eloquent\\Factories\\Factory',
                   'attributes' => 
                  array (
                    'startLine' => 2,
                    'endLine' => 2,
                  ),
                )),
                 'default' => NULL,
                 'lowerBound' => NULL,
                 'description' => '',
                 'attributes' => 
                array (
                  'startLine' => 2,
                  'endLine' => 2,
                ),
              )),
            ),
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
         'traitData' => 
        array (
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Cart.php',
          1 => 'App\\Models\\Cart',
          2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\CartFactory> */',
        ),
      )),
      'e84201844ce9601f5791fc16c0cdcc6f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
         'uses' => 
        array (
          'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'newFactory',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
           'uses' => 
          array (
            'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
          ),
           'className' => 'App\\Models\\Cart',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
            'TFactory' => 
            array (
              0 => '@template',
              1 => 
              \PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode::__set_state(array(
                 'name' => 'TFactory',
                 'bound' => 
                \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode::__set_state(array(
                   'name' => '\\Illuminate\\Database\\Eloquent\\Factories\\Factory',
                   'attributes' => 
                  array (
                    'startLine' => 2,
                    'endLine' => 2,
                  ),
                )),
                 'default' => NULL,
                 'lowerBound' => NULL,
                 'description' => '',
                 'attributes' => 
                array (
                  'startLine' => 2,
                  'endLine' => 2,
                ),
              )),
            ),
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
         'traitData' => 
        array (
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Cart.php',
          1 => 'App\\Models\\Cart',
          2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\CartFactory> */',
        ),
      )),
      '20c9cf9dbbe4917ec1f7275d9170e8e8' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
         'uses' => 
        array (
          'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'getUseFactoryAttribute',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
           'uses' => 
          array (
            'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
          ),
           'className' => 'App\\Models\\Cart',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
            'TFactory' => 
            array (
              0 => '@template',
              1 => 
              \PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode::__set_state(array(
                 'name' => 'TFactory',
                 'bound' => 
                \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode::__set_state(array(
                   'name' => '\\Illuminate\\Database\\Eloquent\\Factories\\Factory',
                   'attributes' => 
                  array (
                    'startLine' => 2,
                    'endLine' => 2,
                  ),
                )),
                 'default' => NULL,
                 'lowerBound' => NULL,
                 'description' => '',
                 'attributes' => 
                array (
                  'startLine' => 2,
                  'endLine' => 2,
                ),
              )),
            ),
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
         'traitData' => 
        array (
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Cart.php',
          1 => 'App\\Models\\Cart',
          2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\CartFactory> */',
        ),
      )),
      '92f40fe2c1e6a0f674b9f1d726ee1e1c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'cartstatus' => 'App\\Enums\\CartStatus',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'casts',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'eabe5a8e798e391016ff22c20f9be914' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'cartstatus' => 'App\\Enums\\CartStatus',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'customer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'abc3a108a7920bb800efcb6779cd12d7' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'cartstatus' => 'App\\Enums\\CartStatus',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'lines',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '9b4a594608692b014e9c913f934053b8' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'cartstatus' => 'App\\Enums\\CartStatus',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Cart',
         'functionName' => 'checkouts',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      '/Users/fabianwesner/Herd/shop/app/Models/Cart.php' => 'a7492a2ffbb453b02a6ecc13b2d2cdb1481ed3d2148487c81ac7aa32bd21b0a4',
      '/Users/fabianwesner/Herd/shop/app/Models/Concerns/BelongsToStore.php' => 'e752b251a2ae55385ea68559849d4231bcd62930665ecc7f76715a4cd9f46ce1',
      '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Eloquent/Factories/HasFactory.php' => 'b6cb2b164e90168e80963a5549541f5f3188a3ec8cfd368bf3611bd94fbd46a7',
    ),
  ),
));