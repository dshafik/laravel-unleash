<?php

namespace MikeFrancis\LaravelUnleash\Tests\Facades;

use MikeFrancis\LaravelUnleash\Facades\Feature;
use MikeFrancis\LaravelUnleash\ServiceProvider;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;

class FeatureTest extends TestCase
{
    public function testFacade()
    {
        Feature::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertEquals(
            new FeatureFlagCollection([
                new FeatureFlag('active-flag', true),
                new FeatureFlag('inactive-flag', false),
            ]),
            Feature::all()
        );

        $this->assertTrue(Feature::enabled('active-flag'));
        $this->assertFalse(Feature::disabled('active-flag'));

        $this->assertTrue(Feature::disabled('inactive-flag'));
        $this->assertFalse(Feature::enabled('inactive-flag'));

        $this->assertTrue(Feature::disabled('unknown-flag'));
        $this->assertFalse(Feature::enabled('unknown-flag'));

        $this->assertEquals(Feature::all(), Feature::getFeatures());

        $this->assertEquals(Feature::get('active-flag'), Feature::getFeature('active-flag'));
        $this->assertEquals(Feature::enabled('active-flag'), Feature::isFeatureEnabled('active-flag'));
        $this->assertEquals(Feature::disabled('active-flag'), Feature::isFeatureDisabled('active-flag'));

        $this->assertEquals(Feature::get('inactive-flag'), Feature::getFeature('inactive-flag'));
        $this->assertEquals(Feature::enabled('inactive-flag'), Feature::isFeatureEnabled('inactive-flag'));
        $this->assertEquals(Feature::disabled('inactive-flag'), Feature::isFeatureDisabled('inactive-flag'));

        $this->assertEquals(Feature::get('unknown-flag'), Feature::getFeature('unknown-flag'));
        $this->assertEquals(Feature::enabled('unknown-flag'), Feature::isFeatureEnabled('unknown-flag'));
        $this->assertEquals(Feature::disabled('unknown-flag'), Feature::isFeatureDisabled('unknown-flag'));
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}