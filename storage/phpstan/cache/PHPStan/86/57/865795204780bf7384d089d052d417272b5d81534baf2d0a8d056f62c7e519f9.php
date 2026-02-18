<?php declare(strict_types = 1);

// ftm-/Users/fabianwesner/Herd/shop/app/Models/Order.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v4-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      'c82416eda19115f7825386a625cb33b5' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'financialstatus' => 'App\\Enums\\FinancialStatus',
          'fulfillmentstatus' => 'App\\Enums\\FulfillmentStatus',
          'orderstatus' => 'App\\Enums\\OrderStatus',
          'paymentmethod' => 'App\\Enums\\PaymentMethod',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Order',
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
      '6bc3c75a2e4985d18fbf68d377b0a0d8' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models\\Concerns',
         'uses' => 
        array (
          'storescope' => 'App\\Models\\Scopes\\StoreScope',
          'store' => 'App\\Models\\Store',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'className' => 'App\\Models\\Order',
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
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Order.php',
          1 => 'App\\Models\\Order',
          2 => 'App\\Models\\Concerns\\BelongsToStore',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\OrderFactory> */',
        ),
      )),
      '7cdb4473650388575cb8883df0de9230' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models\\Concerns',
         'uses' => 
        array (
          'storescope' => 'App\\Models\\Scopes\\StoreScope',
          'store' => 'App\\Models\\Store',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'className' => 'App\\Models\\Order',
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
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Order.php',
          1 => 'App\\Models\\Order',
          2 => 'App\\Models\\Concerns\\BelongsToStore',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\OrderFactory> */',
        ),
      )),
      '89cf6d10cb6c443896d96fabd93ab654' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models\\Concerns',
         'uses' => 
        array (
          'storescope' => 'App\\Models\\Scopes\\StoreScope',
          'store' => 'App\\Models\\Store',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'className' => 'App\\Models\\Order',
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
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Order.php',
          1 => 'App\\Models\\Order',
          2 => 'App\\Models\\Concerns\\BelongsToStore',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\OrderFactory> */',
        ),
      )),
      'c1507bd46d71d2849216d882c5dba520' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
         'uses' => 
        array (
          'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
        ),
         'className' => 'App\\Models\\Order',
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
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Order.php',
          1 => 'App\\Models\\Order',
          2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\OrderFactory> */',
        ),
      )),
      '96a09220312223b3a1515dd98f9f406d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
         'uses' => 
        array (
          'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
        ),
         'className' => 'App\\Models\\Order',
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
           'className' => 'App\\Models\\Order',
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
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Order.php',
          1 => 'App\\Models\\Order',
          2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\OrderFactory> */',
        ),
      )),
      'add19c63dbf9e496696dce5230eab897' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
         'uses' => 
        array (
          'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
        ),
         'className' => 'App\\Models\\Order',
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
           'className' => 'App\\Models\\Order',
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
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Order.php',
          1 => 'App\\Models\\Order',
          2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\OrderFactory> */',
        ),
      )),
      'e5465d968b1bc45f34de5eb34a9127b5' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Illuminate\\Database\\Eloquent\\Factories',
         'uses' => 
        array (
          'usefactory' => 'Illuminate\\Database\\Eloquent\\Attributes\\UseFactory',
        ),
         'className' => 'App\\Models\\Order',
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
           'className' => 'App\\Models\\Order',
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
          0 => '/Users/fabianwesner/Herd/shop/app/Models/Order.php',
          1 => 'App\\Models\\Order',
          2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          3 => NULL,
          4 => '/** @use HasFactory<\\Database\\Factories\\OrderFactory> */',
        ),
      )),
      '5bb74d3eb75b7b094151590f6622eff2' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'financialstatus' => 'App\\Enums\\FinancialStatus',
          'fulfillmentstatus' => 'App\\Enums\\FulfillmentStatus',
          'orderstatus' => 'App\\Enums\\OrderStatus',
          'paymentmethod' => 'App\\Enums\\PaymentMethod',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Order',
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
      '80468509da9cf6bd1b1fc6c38ca0176f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'financialstatus' => 'App\\Enums\\FinancialStatus',
          'fulfillmentstatus' => 'App\\Enums\\FulfillmentStatus',
          'orderstatus' => 'App\\Enums\\OrderStatus',
          'paymentmethod' => 'App\\Enums\\PaymentMethod',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Order',
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
      '1b6ac1762db60b627cf3e602ea0530bf' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'financialstatus' => 'App\\Enums\\FinancialStatus',
          'fulfillmentstatus' => 'App\\Enums\\FulfillmentStatus',
          'orderstatus' => 'App\\Enums\\OrderStatus',
          'paymentmethod' => 'App\\Enums\\PaymentMethod',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Order',
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
      '7cbda6a18135c09671994be0934e1355' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'financialstatus' => 'App\\Enums\\FinancialStatus',
          'fulfillmentstatus' => 'App\\Enums\\FulfillmentStatus',
          'orderstatus' => 'App\\Enums\\OrderStatus',
          'paymentmethod' => 'App\\Enums\\PaymentMethod',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Order',
         'functionName' => 'payments',
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
      '7bdf7a54ad52a4fd6d9ca6d92f7f9b79' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'financialstatus' => 'App\\Enums\\FinancialStatus',
          'fulfillmentstatus' => 'App\\Enums\\FulfillmentStatus',
          'orderstatus' => 'App\\Enums\\OrderStatus',
          'paymentmethod' => 'App\\Enums\\PaymentMethod',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Order',
         'functionName' => 'refunds',
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
      '55dae58faf37367863ab716e1dba1f8e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Models',
         'uses' => 
        array (
          'financialstatus' => 'App\\Enums\\FinancialStatus',
          'fulfillmentstatus' => 'App\\Enums\\FulfillmentStatus',
          'orderstatus' => 'App\\Enums\\OrderStatus',
          'paymentmethod' => 'App\\Enums\\PaymentMethod',
          'belongstostore' => 'App\\Models\\Concerns\\BelongsToStore',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'className' => 'App\\Models\\Order',
         'functionName' => 'fulfillments',
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
      '/Users/fabianwesner/Herd/shop/app/Models/Order.php' => '2e920c171e185e93862eb28ba6d1e8c76f8f7178ae0a81ae408a737f472da50d',
      '/Users/fabianwesner/Herd/shop/app/Models/Concerns/BelongsToStore.php' => 'e752b251a2ae55385ea68559849d4231bcd62930665ecc7f76715a4cd9f46ce1',
      '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Database/Eloquent/Factories/HasFactory.php' => 'b6cb2b164e90168e80963a5549541f5f3188a3ec8cfd368bf3611bd94fbd46a7',
    ),
  ),
));