<?php declare(strict_types = 1);

// osfsl-/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Support/Facades/Date.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Support\Facades\Date
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-0fb55c088552255e2302f29a73e9761521d5353e064abe154626f0c8f0aaf93c-8.4.17-6.65.0.9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Support\\Facades\\Date',
        'filename' => '/Users/fabianwesner/Herd/shop/vendor/composer/../laravel/framework/src/Illuminate/Support/Facades/Date.php',
      ),
    ),
    'namespace' => 'Illuminate\\Support\\Facades',
    'name' => 'Illuminate\\Support\\Facades\\Date',
    'shortName' => 'Date',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @see https://carbon.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php
 *
 * @method static mixed use(mixed $handler)
 * @method static void useDefault()
 * @method static void useCallable(callable $callable)
 * @method static void useClass(string $dateClass)
 * @method static void useFactory(object $factory)
 * @method static bool canBeCreatedFromFormat(?string $date, string $format)
 * @method static \\Illuminate\\Support\\Carbon|null create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon createFromDate($year = null, $month = null, $day = null, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon|null createFromFormat($format, $time, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon|null createFromIsoFormat(string $format, string $time, $timezone = null, ?string $locale = \'en\', ?\\Symfony\\Contracts\\Translation\\TranslatorInterface $translator = null)
 * @method static \\Illuminate\\Support\\Carbon|null createFromLocaleFormat(string $format, string $locale, string $time, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon|null createFromLocaleIsoFormat(string $format, string $locale, string $time, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon createFromTime($hour = 0, $minute = 0, $second = 0, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon createFromTimeString(string $time, \\DateTimeZone|string|int|null $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon createFromTimestamp(string|int|float $timestamp, \\DateTimeZone|string|int|null $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon createFromTimestampMs(string|int|float $timestamp, \\DateTimeZone|string|int|null $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon createFromTimestampMsUTC($timestamp)
 * @method static \\Illuminate\\Support\\Carbon createFromTimestampUTC(string|int|float $timestamp)
 * @method static \\Illuminate\\Support\\Carbon createMidnightDate($year = null, $month = null, $day = null, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon|null createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon createStrict(?int $year = 0, ?int $month = 1, ?int $day = 1, ?int $hour = 0, ?int $minute = 0, ?int $second = 0, $timezone = null)
 * @method static void disableHumanDiffOption($humanDiffOption)
 * @method static void enableHumanDiffOption($humanDiffOption)
 * @method static mixed executeWithLocale(string $locale, callable $func)
 * @method static \\Illuminate\\Support\\Carbon fromSerialized($value)
 * @method static array getAvailableLocales()
 * @method static array getAvailableLocalesInfo()
 * @method static array getDays()
 * @method static ?string getFallbackLocale()
 * @method static array getFormatsToIsoReplacements()
 * @method static int getHumanDiffOptions()
 * @method static array getIsoUnits()
 * @method static array|false getLastErrors()
 * @method static string getLocale()
 * @method static int getMidDayAt()
 * @method static string getTimeFormatByPrecision(string $unitPrecision)
 * @method static string|\\Closure|null getTranslationMessageWith($translator, string $key, ?string $locale = null, ?string $default = null)
 * @method static \\Illuminate\\Support\\Carbon|null getTestNow()
 * @method static \\Symfony\\Contracts\\Translation\\TranslatorInterface getTranslator()
 * @method static int getWeekEndsAt(?string $locale = null)
 * @method static int getWeekStartsAt(?string $locale = null)
 * @method static array getWeekendDays()
 * @method static bool hasFormat(string $date, string $format)
 * @method static bool hasFormatWithModifiers(string $date, string $format)
 * @method static bool hasMacro($name)
 * @method static bool hasRelativeKeywords(?string $time)
 * @method static bool hasTestNow()
 * @method static \\Illuminate\\Support\\Carbon instance(\\DateTimeInterface $date)
 * @method static bool isImmutable()
 * @method static bool isModifiableUnit($unit)
 * @method static bool isMutable()
 * @method static bool isStrictModeEnabled()
 * @method static bool localeHasDiffOneDayWords(string $locale)
 * @method static bool localeHasDiffSyntax(string $locale)
 * @method static bool localeHasDiffTwoDayWords(string $locale)
 * @method static bool localeHasPeriodSyntax($locale)
 * @method static bool localeHasShortUnits(string $locale)
 * @method static void macro(string $name, ?callable $macro)
 * @method static \\Illuminate\\Support\\Carbon|null make($var, \\DateTimeZone|string|null $timezone = null)
 * @method static void mixin(object|string $mixin)
 * @method static \\Illuminate\\Support\\Carbon now(\\DateTimeZone|string|int|null $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon parse(\\DateTimeInterface|\\Carbon\\WeekDay|\\Carbon\\Month|string|int|float|null $time, \\DateTimeZone|string|int|null $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon parseFromLocale(string $time, ?string $locale = null, \\DateTimeZone|string|int|null $timezone = null)
 * @method static string pluralUnit(string $unit)
 * @method static \\Illuminate\\Support\\Carbon|null rawCreateFromFormat(string $format, string $time, $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon rawParse(\\DateTimeInterface|\\Carbon\\WeekDay|\\Carbon\\Month|string|int|float|null $time, \\DateTimeZone|string|int|null $timezone = null)
 * @method static void resetMonthsOverflow()
 * @method static void resetToStringFormat()
 * @method static void resetYearsOverflow()
 * @method static void serializeUsing($callback)
 * @method static void setFallbackLocale(string $locale)
 * @method static void setHumanDiffOptions($humanDiffOptions)
 * @method static void setLocale(string $locale)
 * @method static void setMidDayAt($hour)
 * @method static void setTestNow(mixed $testNow = null)
 * @method static void setTestNowAndTimezone(mixed $testNow = null, $timezone = null)
 * @method static void setToStringFormat(string|\\Closure|null $format)
 * @method static void setTranslator(\\Symfony\\Contracts\\Translation\\TranslatorInterface $translator)
 * @method static void setWeekEndsAt($day)
 * @method static void setWeekStartsAt($day)
 * @method static void setWeekendDays($days)
 * @method static bool shouldOverflowMonths()
 * @method static bool shouldOverflowYears()
 * @method static string singularUnit(string $unit)
 * @method static void sleep(int|float $seconds)
 * @method static \\Illuminate\\Support\\Carbon today(\\DateTimeZone|string|int|null $timezone = null)
 * @method static \\Illuminate\\Support\\Carbon tomorrow(\\DateTimeZone|string|int|null $timezone = null)
 * @method static string translateTimeString(string $timeString, ?string $from = null, ?string $to = null, int $mode = \\Carbon\\CarbonInterface::TRANSLATE_ALL)
 * @method static string translateWith(\\Symfony\\Contracts\\Translation\\TranslatorInterface $translator, string $key, array $parameters = [], $number = null)
 * @method static void useMonthsOverflow($monthsOverflow = true)
 * @method static void useStrictMode($strictModeEnabled = true)
 * @method static void useYearsOverflow($yearsOverflow = true)
 * @method static mixed withTestNow(mixed $testNow, callable $callback)
 * @method static static withTimeZone(\\DateTimeZone|string|int|null $timezone)
 * @method static \\Illuminate\\Support\\Carbon yesterday(\\DateTimeZone|string|int|null $timezone = null)
 *
 * @see \\Illuminate\\Support\\DateFactory
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 109,
    'endLine' => 141,
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
      'DEFAULT_FACADE' => 
      array (
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Date',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Date',
        'name' => 'DEFAULT_FACADE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\Illuminate\\Support\\DateFactory::class',
          'attributes' => 
          array (
            'startLine' => 111,
            'endLine' => 111,
            'startTokenPos' => 30,
            'startFilePos' => 7681,
            'endTokenPos' => 32,
            'endFilePos' => 7698,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
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
 *
 * @throws \\RuntimeException
 */',
        'startLine' => 120,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'Illuminate\\Support\\Facades',
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Date',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Date',
        'currentClassName' => 'Illuminate\\Support\\Facades\\Date',
        'aliasName' => NULL,
      ),
      'resolveFacadeInstance' => 
      array (
        'name' => 'resolveFacadeInstance',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 53,
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
 * Resolve the facade root instance from the container.
 *
 * @param  string  $name
 * @return mixed
 */',
        'startLine' => 131,
        'endLine' => 140,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'Illuminate\\Support\\Facades',
        'declaringClassName' => 'Illuminate\\Support\\Facades\\Date',
        'implementingClassName' => 'Illuminate\\Support\\Facades\\Date',
        'currentClassName' => 'Illuminate\\Support\\Facades\\Date',
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