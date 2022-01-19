<?php

namespace MikeFrancis\LaravelUnleash\Tests\Facades;

use MikeFrancis\LaravelUnleash\Facades\Unleash;
use MikeFrancis\LaravelUnleash\ServiceProvider;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;

class UnleashTest extends TestCase
{
    public function testMethodAliases()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertEquals(
            new FeatureFlagCollection([
                new FeatureFlag('active-flag', true),
                new FeatureFlag('inactive-flag', false),
            ]),
            Unleash::all()
        );

        $this->assertTrue(Unleash::enabled('active-flag'));
        $this->assertFalse(Unleash::disabled('active-flag'));

        $this->assertTrue(Unleash::disabled('inactive-flag'));
        $this->assertFalse(Unleash::enabled('inactive-flag'));

        $this->assertTrue(Unleash::disabled('unknown-flag'));
        $this->assertFalse(Unleash::enabled('unknown-flag'));

        $this->assertEquals(Unleash::all(), Unleash::getFeatures());

        $this->assertEquals(Unleash::get('active-flag'), Unleash::getFeature('active-flag'));
        $this->assertEquals(Unleash::enabled('active-flag'), Unleash::isFeatureEnabled('active-flag'));
        $this->assertEquals(Unleash::disabled('active-flag'), Unleash::isFeatureDisabled('active-flag'));

        $this->assertEquals(Unleash::get('inactive-flag'), Unleash::getFeature('inactive-flag'));
        $this->assertEquals(Unleash::enabled('inactive-flag'), Unleash::isFeatureEnabled('inactive-flag'));
        $this->assertEquals(Unleash::disabled('inactive-flag'), Unleash::isFeatureDisabled('inactive-flag'));

        $this->assertEquals(Unleash::get('unknown-flag'), Unleash::getFeature('unknown-flag'));
        $this->assertEquals(Unleash::enabled('unknown-flag'), Unleash::isFeatureEnabled('unknown-flag'));
        $this->assertEquals(Unleash::disabled('unknown-flag'), Unleash::isFeatureDisabled('unknown-flag'));
    }

    public function testBasicMock()
    {
        Unleash::shouldReceive('enabled')->with('active')->andReturnTrue();
        $this->assertTrue(Unleash::enabled('active'));

        Unleash::shouldReceive('disabled')->with('inactive')->andReturnTrue();
        $this->assertTrue(Unleash::disabled('inactive'));
    }

    public function testEnabledFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true)
        ]));

        $this->assertTrue(Unleash::enabled('active-flag'));

        $this->assertFalse(Unleash::disabled('active-flag'));

        $this->assertEquals(new FeatureFlag('active-flag', true), Unleash::get('active-flag'));

        $this->assertEquals([new FeatureFlag('active-flag', true)], Unleash::all()->all());
    }

    public function testDisabledFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('inactive-flag', false)
        ]));

        $this->assertTrue(Unleash::disabled('inactive-flag'));

        $this->assertFalse(Unleash::enabled('inactive-flag'));

        $this->assertEquals(new FeatureFlag('inactive-flag', false), Unleash::get('inactive-flag'));

        $this->assertEquals([new FeatureFlag('inactive-flag', false)], Unleash::all()->all());
    }

    public function testMixedFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertTrue(Unleash::enabled('active-flag'));
        $this->assertFalse(Unleash::disabled('active-flag'));

        $this->assertTrue(Unleash::disabled('inactive-flag'));
        $this->assertFalse(Unleash::enabled('inactive-flag'));

        $this->assertTrue(Unleash::disabled('unknown-flag'));
        $this->assertFalse(Unleash::enabled('unknown-flag'));
    }

    public function testEnabledWithArgsFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            (new FeatureFlag('active-flag', true))->withTestArgs('foo'),
            (new FeatureFlag('active-flag', true))->withTestArgs('foo', 'bar'),
        ]));

        $this->assertTrue(Unleash::enabled('active-flag', 'foo'));
        $this->assertFalse(Unleash::disabled('active-flag', 'foo'));

        $this->assertTrue(Unleash::enabled('active-flag', 'foo', 'bar'));
        $this->assertFalse(Unleash::disabled('active-flag', 'foo', 'bar'));

        $this->assertFalse(Unleash::enabled('active-flag'));
        $this->assertTrue(Unleash::disabled('active-flag'));


        $this->assertFalse(Unleash::enabled('active-flag', 'foo', 'bar', 'baz'));
        $this->assertTrue(Unleash::disabled('active-flag', 'foo', 'bar', 'baz'));
    }

    public function testEnabledNotFake()
    {
        $this->assertFalse(Unleash::enabled('unknown-flag'));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testDisabledNotFake()
    {
        $this->assertFalse(Unleash::enabled('unknown-flag'));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testGetWithFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertEquals(new FeatureFlag('active-flag', true), Unleash::get('active-flag'));

        $this->assertEquals(new FeatureFlag('inactive-flag', false), Unleash::get('inactive-flag'));
    }

    public function testGetWithArgsFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            (new FeatureFlag('active-flag', true))->withTestArgs('foo'),
            (new FeatureFlag('active-flag', false))->withTestArgs('foo', 'bar'),
            (new FeatureFlag('inactive-flag', false))->withTestArgs('foo'),
            (new FeatureFlag('inactive-flag', true))->withTestArgs('foo', 'bar'),
        ]));

        $this->assertEquals((new FeatureFlag('active-flag', true))->withTestArgs('foo'), Unleash::get('active-flag'));

        $this->assertEquals((new FeatureFlag('inactive-flag', false))->withTestArgs('foo'), Unleash::get('inactive-flag'));
    }

    public function testAllWithFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertEquals(
            new FeatureFlagCollection([
                new FeatureFlag('active-flag', true),
                new FeatureFlag('inactive-flag', false),
            ]),
            Unleash::all()
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function tearDown(): void
    {
        Unleash::clearResolvedInstances();
    }
}