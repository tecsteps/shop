<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/fortify/src/Actions/ConfirmTwoFactorAuthentication.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-f73b9ac79242de09df85b9fa1c12cb154e294410987dbf76ed2078c276d3a7df-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/fortify/src/Actions/ConfirmTwoFactorAuthentication.php',
      ),
    ),
    'namespace' => 'Laravel\\Fortify\\Actions',
    'name' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
    'shortName' => 'ConfirmTwoFactorAuthentication',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 53,
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
      'provider' => 
      array (
        'declaringClassName' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
        'implementingClassName' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
        'name' => 'provider',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The two factor authentication provider.
 *
 * @var \\Laravel\\Fortify\\Contracts\\TwoFactorAuthenticationProvider
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 24,
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
          'provider' => 
          array (
            'name' => 'provider',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Laravel\\Fortify\\Contracts\\TwoFactorAuthenticationProvider',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 33,
            'endColumn' => 73,
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
 * Create a new action instance.
 *
 * @param  \\Laravel\\Fortify\\Contracts\\TwoFactorAuthenticationProvider  $provider
 * @return void
 */',
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Laravel\\Fortify\\Actions',
        'declaringClassName' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
        'implementingClassName' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
        'currentClassName' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
        'aliasName' => NULL,
      ),
      '__invoke' => 
      array (
        'name' => '__invoke',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 30,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'code' => 
          array (
            'name' => 'code',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 37,
            'endColumn' => 41,
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
 * Confirm the two factor authentication configuration for the user.
 *
 * @param  mixed  $user
 * @param  string  $code
 * @return void
 */',
        'startLine' => 37,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Laravel\\Fortify\\Actions',
        'declaringClassName' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
        'implementingClassName' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
        'currentClassName' => 'Laravel\\Fortify\\Actions\\ConfirmTwoFactorAuthentication',
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