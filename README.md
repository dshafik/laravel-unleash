# Laravel Unleash

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/56b0c6402eca49169cbeb3f404c2bff9)](https://app.codacy.com/manual/mikefrancis/laravel-unleash?utm_source=github.com&utm_medium=referral&utm_content=mikefrancis/laravel-unleash&utm_campaign=Badge_Grade_Dashboard)
[![Packagist](https://img.shields.io/packagist/v/mikefrancis/laravel-unleash)](https://packagist.org/packages/mikefrancis/laravel-unleash) [![Build Status](https://github.com/mikefrancis/laravel-unleash/workflows/CI/badge.svg)](https://github.com/mikefrancis/laravel-unleash/actions?query=workflow%3ACI) [![codecov](https://codecov.io/gh/mikefrancis/laravel-unleash/branch/master/graph/badge.svg)](https://codecov.io/gh/mikefrancis/laravel-unleash)

An [Unleash](https://unleash.github.io) client for Laravel.

## Installation

```bash
composer require mikefrancis/laravel-unleash
```

Export package config:

```bash
php artisan vendor:publish --provider="MikeFrancis\LaravelUnleash\ServiceProvider"
```

## Configuration

Documentation for configuration can be found in [config/unleash.php](https://github.com/mikefrancis/laravel-unleash/blob/master/config/unleash.php).

## Usage

```php
use \MikeFrancis\LaravelUnleash\Unleash;

$unleash = app(Unleash::class);

if ($unleash->enabled('myAwesomeFeature')) {
  // Congratulations, you can see this awesome feature!
}

if ($unleash->disabled('myAwesomeFeature')) {
  // Check back later for more features!
}

$feature = $unleash->get('myAwesomeFeature');

$allFeatures = $unleash->all();
```

### Variants

To use variant support, define your variants on the feature and use:

```php
use \MikeFrancis\LaravelUnleash\Unleash;

$unleash = app(Unleash::class);

$defaultColor = '#000';
$color = $unleash->variant('title-color', $defaultColor);
```

This will return the correct variant for the user, or the default if the feature flag is disabled or no valid variant is found.

> **Note:** You can combine variants with strategies.

### Facades

Using the `Feature` facade:

```php
use Feature;

if (Feature::enabled('myAwesomeFeature')) {
  // Congratulations, you can see this awesome feature!
}

if (Feature::disabled('myAwesomeFeature')) {
  // Check back later for more features!
}

$feature = Feature::get('myAwesomeFeature');

$allFeatures = Feature::all();
```

Or, you can use the `Unleash` facade, which is consistent with standard Unleash clients:

```php
use Unleash;

if (Unleash::isFeatureEnabled('myAwesomeFeature')) {
  // Congratulations, you can see this awesome feature!
}

if (Unleash::isFeatureDisabled('myAwesomeFeature')) {
  // Check back later for more features!
}

$feature = Unleash::getFeature('myAwesomeFeature');

$allFeatures = Unleash::getFeatures();
```

### Dynamic Arguments

If your strategy relies on dynamic data at runtime, you can pass additional arguments to the feature check functions:

```php
use \MikeFrancis\LaravelUnleash\Unleash;
use Config;

$unleash = app(Unleash::class);

$allowList = config('app.allow_list');

if ($unleash->enabled('myAwesomeFeature', $allowList)) {
  // Congratulations, you can see this awesome feature!
}

if ($unleash->disabled('myAwesomeFeature', $allowList)) {
  // Check back later for more features!
}
```

### Blade

Blade directive for checking if a feature is **enabled**:

```php
@featureEnabled('myAwesomeFeature')
Congratulations, you can see this awesome feature!
@endfeatureEnabled
```

Or if a feature is **disabled**:

```php
@featureDisabled('myAwesomeFeature')
Check back later for more features!
@endfeatureDisabled
```

You cannot currently use dynamic strategy arguments with Blade template directives.

### Middleware

This package includes middleware that will deny routes depending on whether a feature is enabled or not.

To use the middle, add the following to your `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // other middleware
    'feature.enabled' => \MikeFrancis\LaravelUnleash\Middleware\FeatureEnabled::class,
    'feature.disabled' => \MikeFrancis\LaravelUnleash\Middleware\FeatureDisabled::class,
];
```

You can then use the middleware in your routes:

```php
Route::get('/new-feature-path', function () {
    //
})->middleware('feature.enabled:myAwesomeFeature');

Route::get('/terrible-legacy-path', function () {
    //
})->middleware('feature.disabled:myAwesomeFeature');
```

or in your controllers like so:

```php
class ExampleController extends Controller
{
    public function __construct()
    {
        $this->middleware('feature.enabled:myAwesomeFeature');
        // or
        $this->middleware('feature.disabled:myAwesomeFeature');
    }
}
```

You cannot currently use dynamic strategy arguments with Middleware.