<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Support/Facades/Auth.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Support\Facades\Auth
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-29d8b7cc5fd4d37bd472abefdc8de2785c75f4bbe7a911def9b84fa3aca6a8ed-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Support\\Facades\\Auth',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Support/Facades/Auth.php',
      ),
    ),
    'namespace' => 'Illuminate\\Support\\Facades',
    'name' => 'Illuminate\\Support\\Facades\\Auth',
    'shortName' => 'Auth',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @method static \\Illuminate\\Contracts\\Auth\\Guard|\\Illuminate\\Contracts\\Auth\\StatefulGuard guard(string|null $name = null)
 * @method static \\Illuminate\\Auth\\SessionGuard createSessionDriver(string $name, array $config)
 * @method static \\Illuminate\\Auth\\TokenGuard createTokenDriver(string $name, array $config)
 * @method static string getDefaultDriver()
 * @method static void shouldUse(string $name)
 * @method static void setDefaultDriver(string $name)
 * @method static \\Illuminate\\Auth\\AuthManager viaRequest(string $driver, callable $callback)
 * @method static \\Closure userResolver()
 * @method static \\Illuminate\\Auth\\AuthManager resolveUsersUsing(\\Closure $userResolver)
 * @method static \\Illuminate\\Auth\\AuthManager extend(string $driver, \\Closure $callback)
 * @method static \\Illuminate\\Auth\\AuthManager provider(string $name, \\Closure $callback)
 * @method static bool hasResolvedGuards()
 * @method static \\Illuminate\\Auth\\AuthManager forgetGuards()
 * @method static \\Illuminate\\Auth\\AuthManager setApplication(\\Illuminate\\Contracts\\Foundation\\Application $app)
 * @method static \\Illuminate\\Contracts\\Auth\\UserProvider|null createUserProvider(string|null $provider = null)
 * @method static string getDefaultUserProvider()
 * @method static bool check()
 * @method static bool guest()
 * @method static \\Illuminate\\Contracts\\Auth\\Authenticatable|null user()
 * @method static int|string|null id()
 * @method static bool validate(array $credentials = [])
 * @method static bool hasUser()
 * @method static \\Illuminate\\Contracts\\Auth\\Guard setUser(\\Illuminate\\Contracts\\Auth\\Authenticatable $user)
 * @method static bool attempt(array $credentials = [], bool $remember = false)
 * @method static bool once(array $credentials = [])
 * @method static void login(\\Illuminate\\Contracts\\Auth\\Authenticatable $user, bool $remember = false)
 * @method static \\Illuminate\\Contracts\\Auth\\Authenticatable|false loginUsingId(mixed $id, bool $remember = false)
 * @method static \\Illuminate\\Contracts\\Auth\\Authenticatable|false onceUsingId(mixed $id)
 * @method static bool viaRemember()
 * @method static void logout()
 * @method static \\Symfony\\Component\\HttpFoundation\\Response|null basic(string $field = \'email\', array $extraConditions = [])
 * @method static \\Symfony\\Component\\HttpFoundation\\Response|null onceBasic(string $field = \'email\', array $extraConditions = [])
 * @method static bool attemptWhen(array $credentials = [], array|callable|null $callbacks = null, bool $remember = false)
 * @method static string hashPasswordForCookie(string $passwordHash)
 * @method static void logoutCurrentDevice()
 * @method static \\Illuminate\\Contracts\\Auth\\Authenticatable|null logoutOtherDevices(string $password)
 * @method static void attempting(mixed $callback)
 * @method static \\Illuminate\\Contracts\\Auth\\Authenticatable getLastAttempted()
 * @method static string getName()
 * @method static string getRecallerName()
 * @method static \\Illuminate\\Auth\\SessionGuard setRememberDuration(int $minutes)
 * @method static \\Illuminate\\Contracts\\Cookie\\QueueingFactory getCookieJar()
 * @method static void setCookieJar(\\Illuminate\\Contracts\\Cookie\\QueueingFactory $cookie)
 * @method static \\Illuminate\\Contracts\\Events\\Dispatcher getDispatcher()
 * @method static void setDispatcher(\\Illuminate\\Contracts\\Events\\Dispatcher $events)
 * @method static \\Illuminate\\Contracts\\Session\\Session getSession()
 * @method static \\Illuminate\\Contracts\\Auth\\Authenticatable|null getUser()
 * @method static \\Symfony\\Component\\HttpFoundation\\Request getRequest()
 * @method static \\Illuminate\\Auth\\SessionGuard setRequest(\\Symfony\\Component\\HttpFoundation\\Request $request)
 * @method static \\Illuminate\\Support\\Timebox getTimebox()
 * @method static \\Illuminate\\Contracts\\Auth\\Authenticatable authenticate()
 * @method static \\Illuminate\\Auth\\SessionGuard forgetUser()
 * @method static \\Illuminate\\Contracts\\Auth\\UserProvider getProvider()
 * @method static void setProvider(\\Illuminate\\Contracts\\Auth\\UserProvider $provider)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \\Illuminate\\Auth\\AuthManager
 * @see \\Illuminate\\Auth\\SessionGuard
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 71,
    'endLine' => 99,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Support\\Facades\\Facade',
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
      'getFacadeAccessor' => 
      array (
        'name' => 'getFacadeAccessor',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the registered name of the component.
 *
 * @return string
 */',
        'startLine' => 78,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'Illuminate\\Support\\Facades',
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Auth',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Auth',
        'currentClassName' => 'Illuminate\\Support\\Facades\\Auth',
        'aliasName' => NULL,
      ),
      'routes' => 
      array (
        'name' => 'routes',
        'parameters' => 
        array (
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 91,
                'endLine' => 91,
                'startTokenPos' => 66,
                'startFilePos' => 4873,
                'endTokenPos' => 67,
                'endFilePos' => 4874,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 35,
            'endColumn' => 53,
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
 * Register the typical authentication routes for an application.
 *
 * @param  array  $options
 * @return void
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 91,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support\\Facades',
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Auth',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Auth',
        'currentClassName' => 'Illuminate\\Support\\Facades\\Auth',
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