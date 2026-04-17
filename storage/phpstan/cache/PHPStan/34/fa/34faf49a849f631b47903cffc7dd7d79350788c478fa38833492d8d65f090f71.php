<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Support/DateFactory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Support\DateFactory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-a9da4819ec66bfcc528b2497d3fe39f665da01b132b35b6806ad27808cdab890-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Support\\DateFactory',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Support/DateFactory.php',
      ),
    ),
    'namespace' => 'Illuminate\\Support',
    'name' => 'Illuminate\\Support\\DateFactory',
    'shortName' => 'DateFactory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @see https://carbon.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php
 *
 * @method bool canBeCreatedFromFormat(?string $date, string $format)
 * @method \\Illuminate\\Support\\Carbon|null create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon createFromDate($year = null, $month = null, $day = null, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon|null createFromFormat($format, $time, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon|null createFromIsoFormat(string $format, string $time, $timezone = null, ?string $locale = \'en\', ?\\Symfony\\Contracts\\Translation\\TranslatorInterface $translator = null)
 * @method \\Illuminate\\Support\\Carbon|null createFromLocaleFormat(string $format, string $locale, string $time, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon|null createFromLocaleIsoFormat(string $format, string $locale, string $time, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon createFromTime($hour = 0, $minute = 0, $second = 0, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon createFromTimeString(string $time, \\DateTimeZone|string|int|null $timezone = null)
 * @method \\Illuminate\\Support\\Carbon createFromTimestamp(string|int|float $timestamp, \\DateTimeZone|string|int|null $timezone = null)
 * @method \\Illuminate\\Support\\Carbon createFromTimestampMs(string|int|float $timestamp, \\DateTimeZone|string|int|null $timezone = null)
 * @method \\Illuminate\\Support\\Carbon createFromTimestampMsUTC($timestamp)
 * @method \\Illuminate\\Support\\Carbon createFromTimestampUTC(string|int|float $timestamp)
 * @method \\Illuminate\\Support\\Carbon createMidnightDate($year = null, $month = null, $day = null, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon|null createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon createStrict(?int $year = 0, ?int $month = 1, ?int $day = 1, ?int $hour = 0, ?int $minute = 0, ?int $second = 0, $timezone = null)
 * @method void disableHumanDiffOption($humanDiffOption)
 * @method void enableHumanDiffOption($humanDiffOption)
 * @method mixed executeWithLocale(string $locale, callable $func)
 * @method \\Illuminate\\Support\\Carbon fromSerialized($value)
 * @method array getAvailableLocales()
 * @method array getAvailableLocalesInfo()
 * @method array getDays()
 * @method ?string getFallbackLocale()
 * @method array getFormatsToIsoReplacements()
 * @method int getHumanDiffOptions()
 * @method array getIsoUnits()
 * @method array|false getLastErrors()
 * @method string getLocale()
 * @method int getMidDayAt()
 * @method string getTimeFormatByPrecision(string $unitPrecision)
 * @method string|\\Closure|null getTranslationMessageWith($translator, string $key, ?string $locale = null, ?string $default = null)
 * @method \\Illuminate\\Support\\Carbon|null getTestNow()
 * @method \\Symfony\\Contracts\\Translation\\TranslatorInterface getTranslator()
 * @method int getWeekEndsAt(?string $locale = null)
 * @method int getWeekStartsAt(?string $locale = null)
 * @method array getWeekendDays()
 * @method bool hasFormat(string $date, string $format)
 * @method bool hasFormatWithModifiers(string $date, string $format)
 * @method bool hasMacro($name)
 * @method bool hasRelativeKeywords(?string $time)
 * @method bool hasTestNow()
 * @method \\Illuminate\\Support\\Carbon instance(\\DateTimeInterface $date)
 * @method bool isImmutable()
 * @method bool isModifiableUnit($unit)
 * @method bool isMutable()
 * @method bool isStrictModeEnabled()
 * @method bool localeHasDiffOneDayWords(string $locale)
 * @method bool localeHasDiffSyntax(string $locale)
 * @method bool localeHasDiffTwoDayWords(string $locale)
 * @method bool localeHasPeriodSyntax($locale)
 * @method bool localeHasShortUnits(string $locale)
 * @method void macro(string $name, ?callable $macro)
 * @method \\Illuminate\\Support\\Carbon|null make($var, \\DateTimeZone|string|null $timezone = null)
 * @method void mixin(object|string $mixin)
 * @method \\Illuminate\\Support\\Carbon now(\\DateTimeZone|string|int|null $timezone = null)
 * @method \\Illuminate\\Support\\Carbon parse(\\DateTimeInterface|\\Carbon\\WeekDay|\\Carbon\\Month|string|int|float|null $time, \\DateTimeZone|string|int|null $timezone = null)
 * @method \\Illuminate\\Support\\Carbon parseFromLocale(string $time, ?string $locale = null, \\DateTimeZone|string|int|null $timezone = null)
 * @method string pluralUnit(string $unit)
 * @method \\Illuminate\\Support\\Carbon|null rawCreateFromFormat(string $format, string $time, $timezone = null)
 * @method \\Illuminate\\Support\\Carbon rawParse(\\DateTimeInterface|\\Carbon\\WeekDay|\\Carbon\\Month|string|int|float|null $time, \\DateTimeZone|string|int|null $timezone = null)
 * @method void resetMonthsOverflow()
 * @method void resetToStringFormat()
 * @method void resetYearsOverflow()
 * @method void serializeUsing($callback)
 * @method void setFallbackLocale(string $locale)
 * @method void setHumanDiffOptions($humanDiffOptions)
 * @method void setLocale(string $locale)
 * @method void setMidDayAt($hour)
 * @method void setTestNow(mixed $testNow = null)
 * @method void setTestNowAndTimezone(mixed $testNow = null, $timezone = null)
 * @method void setToStringFormat(string|\\Closure|null $format)
 * @method void setTranslator(\\Symfony\\Contracts\\Translation\\TranslatorInterface $translator)
 * @method void setWeekEndsAt($day)
 * @method void setWeekStartsAt($day)
 * @method void setWeekendDays($days)
 * @method bool shouldOverflowMonths()
 * @method bool shouldOverflowYears()
 * @method string singularUnit(string $unit)
 * @method void sleep(int|float $seconds)
 * @method \\Illuminate\\Support\\Carbon today(\\DateTimeZone|string|int|null $timezone = null)
 * @method \\Illuminate\\Support\\Carbon tomorrow(\\DateTimeZone|string|int|null $timezone = null)
 * @method string translateTimeString(string $timeString, ?string $from = null, ?string $to = null, int $mode = \\Carbon\\CarbonInterface::TRANSLATE_ALL)
 * @method string translateWith(\\Symfony\\Contracts\\Translation\\TranslatorInterface $translator, string $key, array $parameters = [], $number = null)
 * @method void useMonthsOverflow($monthsOverflow = true)
 * @method void useStrictMode($strictModeEnabled = true)
 * @method void useYearsOverflow($yearsOverflow = true)
 * @method mixed withTestNow(mixed $testNow, callable $callback)
 * @method static withTimeZone(\\DateTimeZone|string|int|null $timezone)
 * @method \\Illuminate\\Support\\Carbon yesterday(\\DateTimeZone|string|int|null $timezone = null)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 103,
    'endLine' => 250,
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
      'DEFAULT_CLASS_NAME' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'name' => 'DEFAULT_CLASS_NAME',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\Illuminate\\Support\\Carbon::class',
          'attributes' => 
          array (
            'startLine' => 110,
            'endLine' => 110,
            'startTokenPos' => 33,
            'startFilePos' => 6881,
            'endTokenPos' => 35,
            'endFilePos' => 6893,
          ),
        ),
        'docComment' => '/**
 * The default class that will be used for all created dates.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 110,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
    ),
    'immediateProperties' => 
    array (
      'dateClass' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'name' => 'dateClass',
        'modifiers' => 18,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The type (class) of dates that should be created.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 117,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'callable' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'name' => 'callable',
        'modifiers' => 18,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * This callable may be used to intercept date creation.
 *
 * @var callable
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 124,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'factory' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'name' => 'factory',
        'modifiers' => 18,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The Carbon factory that should be used when creating dates.
 *
 * @var object
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 131,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 30,
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
      'use' => 
      array (
        'name' => 'use',
        'parameters' => 
        array (
          'handler' => 
          array (
            'name' => 'handler',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 32,
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
 * Use the given handler when generating dates (class name, callable, or factory).
 *
 * @param  mixed  $handler
 * @return mixed
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 141,
        'endLine' => 152,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support',
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'currentClassName' => 'Illuminate\\Support\\DateFactory',
        'aliasName' => NULL,
      ),
      'useDefault' => 
      array (
        'name' => 'useDefault',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Use the default date class when generating dates.
 *
 * @return void
 */',
        'startLine' => 159,
        'endLine' => 164,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support',
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'currentClassName' => 'Illuminate\\Support\\DateFactory',
        'aliasName' => NULL,
      ),
      'useCallable' => 
      array (
        'name' => 'useCallable',
        'parameters' => 
        array (
          'callable' => 
          array (
            'name' => 'callable',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 40,
            'endColumn' => 57,
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
 * Execute the given callable on each date creation.
 *
 * @param  callable  $callable
 * @return void
 */',
        'startLine' => 172,
        'endLine' => 178,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support',
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'currentClassName' => 'Illuminate\\Support\\DateFactory',
        'aliasName' => NULL,
      ),
      'useClass' => 
      array (
        'name' => 'useClass',
        'parameters' => 
        array (
          'dateClass' => 
          array (
            'name' => 'dateClass',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 37,
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
 * Use the given date type (class) when generating dates.
 *
 * @param  string  $dateClass
 * @return void
 */',
        'startLine' => 186,
        'endLine' => 192,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support',
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'currentClassName' => 'Illuminate\\Support\\DateFactory',
        'aliasName' => NULL,
      ),
      'useFactory' => 
      array (
        'name' => 'useFactory',
        'parameters' => 
        array (
          'factory' => 
          array (
            'name' => 'factory',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 39,
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
 * Use the given Carbon factory when generating dates.
 *
 * @param  object  $factory
 * @return void
 */',
        'startLine' => 200,
        'endLine' => 206,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Support',
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'currentClassName' => 'Illuminate\\Support\\DateFactory',
        'aliasName' => NULL,
      ),
      '__call' => 
      array (
        'name' => '__call',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 37,
            'endColumn' => 47,
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
 * Handle dynamic calls to generate dates.
 *
 * @param  string  $method
 * @param  array  $parameters
 * @return mixed
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 217,
        'endLine' => 249,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Support',
        'declaringClassName' => 'Illuminate\\Support\\DateFactory',
        'implementingClassName' => 'Illuminate\\Support\\DateFactory',
        'currentClassName' => 'Illuminate\\Support\\DateFactory',
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