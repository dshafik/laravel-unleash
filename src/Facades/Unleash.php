<?php
namespace MikeFrancis\LaravelUnleash\Facades;

use Illuminate\Support\Facades\Facade;
use MikeFrancis\LaravelUnleash\Testing\Fakes\UnleashFake;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;

/**
 * @method static FeatureFlagCollection getFeatures()
 * @method static FeatureFlagCollection all()
 * @method static FeatureFlag getFeature(string $name)
 * @method static FeatureFlag get(string $name)
 * @method static bool isFeatureEnabled(string $feature)
 * @method static bool enabled(string $feature)
 * @method static bool isFeatureDisabled(string $feature)
 * @method static bool disabled(string $feature)
 */
class Unleash extends Facade
{
    static protected $fake;

    public static function fake(FeatureFlagCollection $features)
    {
        static::swap(new UnleashFake(static::getFacadeRoot(), $features));
    }

    protected static function getFacadeAccessor()
    {
        return 'unleash';
    }
}
