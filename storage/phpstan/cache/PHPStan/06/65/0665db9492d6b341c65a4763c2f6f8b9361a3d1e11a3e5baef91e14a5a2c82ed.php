<?php declare(strict_types = 1);

// odsl-/Users/fabianwesner/Herd/shop/app/Services/Payments/MockPaymentProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Services\Payments\MockPaymentProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.65.0.9-8.4.17-c214064bfa2e090aa80e420b3a3b26036f21484ef519406af3f156f8f16c2fd6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Services\\Payments\\MockPaymentProvider',
        'filename' => '/Users/fabianwesner/Herd/shop/app/Services/Payments/MockPaymentProvider.php',
      ),
    ),
    'namespace' => 'App\\Services\\Payments',
    'name' => 'App\\Services\\Payments\\MockPaymentProvider',
    'shortName' => 'MockPaymentProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 84,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'App\\Contracts\\PaymentProvider',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'CARD_SUCCESS' => 
      array (
        'declaringClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'implementingClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'name' => 'CARD_SUCCESS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'4242424242424242\'',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 60,
            'startFilePos' => 342,
            'endTokenPos' => 60,
            'endFilePos' => 359,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
      'CARD_DECLINE' => 
      array (
        'declaringClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'implementingClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'name' => 'CARD_DECLINE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'4000000000000002\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 71,
            'startFilePos' => 396,
            'endTokenPos' => 71,
            'endFilePos' => 413,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
      'CARD_INSUFFICIENT' => 
      array (
        'declaringClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'implementingClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'name' => 'CARD_INSUFFICIENT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'4000000000009995\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 82,
            'startFilePos' => 455,
            'endTokenPos' => 82,
            'endFilePos' => 472,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 57,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'charge' => 
      array (
        'name' => 'charge',
        'parameters' => 
        array (
          'checkout' => 
          array (
            'name' => 'checkout',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Checkout',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 28,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Enums\\PaymentMethod',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 48,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'details' => 
          array (
            'name' => 'details',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 71,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\ValueObjects\\PaymentResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, mixed>  $details
 */',
        'startLine' => 24,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services\\Payments',
        'declaringClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'implementingClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'currentClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'aliasName' => NULL,
      ),
      'refund' => 
      array (
        'name' => 'refund',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Models\\Payment',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'amount' => 
          array (
            'name' => 'amount',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 46,
            'endColumn' => 56,
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
            'name' => 'App\\ValueObjects\\RefundResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 43,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Services\\Payments',
        'declaringClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'implementingClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'currentClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'aliasName' => NULL,
      ),
      'chargeCreditCard' => 
      array (
        'name' => 'chargeCreditCard',
        'parameters' => 
        array (
          'details' => 
          array (
            'name' => 'details',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 39,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'referenceId' => 
          array (
            'name' => 'referenceId',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 55,
            'endColumn' => 73,
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
            'name' => 'App\\ValueObjects\\PaymentResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, mixed>  $details
 */',
        'startLine' => 54,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Services\\Payments',
        'declaringClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'implementingClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
        'currentClassName' => 'App\\Services\\Payments\\MockPaymentProvider',
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