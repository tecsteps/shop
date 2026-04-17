<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../symfony/translation-contracts/TranslatorInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Contracts\Translation\TranslatorInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-0f3dd9aa6abe2a52389eeb817113e975df6f66f227e17f685723c8075430425e-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Contracts\\Translation\\TranslatorInterface',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../symfony/translation-contracts/TranslatorInterface.php',
      ),
    ),
    'namespace' => 'Symfony\\Contracts\\Translation',
    'name' => 'Symfony\\Contracts\\Translation\\TranslatorInterface',
    'shortName' => 'TranslatorInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @author Fabien Potencier <fabien@symfony.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 68,
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
      'trans' => 
      array (
        'name' => 'trans',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 27,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 36,
                'startFilePos' => 2406,
                'endTokenPos' => 37,
                'endFilePos' => 2407,
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 39,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'domain' => 
          array (
            'name' => 'domain',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 47,
                'startFilePos' => 2428,
                'endTokenPos' => 47,
                'endFilePos' => 2431,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 63,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'locale' => 
          array (
            'name' => 'locale',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 57,
                'startFilePos' => 2452,
                'endTokenPos' => 57,
                'endFilePos' => 2455,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 87,
            'endColumn' => 108,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * Translates the given message.
 *
 * When a number is provided as a parameter named "%count%", the message is parsed for plural
 * forms and a translation is chosen according to this number using the following rules:
 *
 * Given a message with different plural translations separated by a
 * pipe (|), this method returns the correct portion of the message based
 * on the given number, locale and the pluralization rules in the message
 * itself.
 *
 * The message supports two different types of pluralization rules:
 *
 * interval: {0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples
 * indexed:  There is one apple|There are %count% apples
 *
 * The indexed solution can also contain labels (e.g. one: There is one apple).
 * This is purely for making the translations more clear - it does not
 * affect the functionality.
 *
 * The two methods can also be mixed:
 *     {0} There are no apples|one: There is one apple|more: There are %count% apples
 *
 * An interval can represent a finite set of numbers:
 *  {1,2,3,4}
 *
 * An interval can represent numbers between two numbers:
 *  [1, +Inf]
 *  ]-1,2[
 *
 * The left delimiter can be [ (inclusive) or ] (exclusive).
 * The right delimiter can be [ (exclusive) or ] (inclusive).
 * Beside numbers, you can use -Inf and +Inf for the infinite.
 *
 * @see https://en.wikipedia.org/wiki/ISO_31-11
 *
 * @param string      $id         The message id (may also be an object that can be cast to string)
 * @param array       $parameters An array of parameters for the message
 * @param string|null $domain     The domain for the message or null to use the default
 * @param string|null $locale     The locale or null to use the default
 *
 * @throws \\InvalidArgumentException If the locale contains invalid characters
 */',
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 118,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Contracts\\Translation',
        'declaringClassName' => 'Symfony\\Contracts\\Translation\\TranslatorInterface',
        'implementingClassName' => 'Symfony\\Contracts\\Translation\\TranslatorInterface',
        'currentClassName' => 'Symfony\\Contracts\\Translation\\TranslatorInterface',
        'aliasName' => NULL,
      ),
      'getLocale' => 
      array (
        'name' => 'getLocale',
        'parameters' => 
        array (
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
 * Returns the default locale.
 */',
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Contracts\\Translation',
        'declaringClassName' => 'Symfony\\Contracts\\Translation\\TranslatorInterface',
        'implementingClassName' => 'Symfony\\Contracts\\Translation\\TranslatorInterface',
        'currentClassName' => 'Symfony\\Contracts\\Translation\\TranslatorInterface',
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