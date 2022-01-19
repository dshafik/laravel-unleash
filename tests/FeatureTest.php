<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Unleash;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use Orchestra\Testbench\TestCase;

class FeatureTest extends TestCase
{
    use MockClient;

    public function testFeatureEnabled()
    {
        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => 'someFeature',
                                'enabled' => true,
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(false);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $feature = $unleash->get('someFeature');
        $this->assertInstanceOf(FeatureFlag::class, $feature);
        $this->assertEquals(new FeatureFlag('someFeature', true), $feature);
        $this->assertArrayHasKey('enabled', $feature);
        $this->assertTrue(isset($feature['enabled']));
        $this->assertTrue(isset($feature->enabled));
        $this->assertTrue($feature['enabled']);
        $this->assertTrue($feature->enabled());
        $this->assertFalse($feature->disabled());
    }

    public function testFeatureDisabled()
    {
        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => 'someFeature',
                                'enabled' => false,
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(false);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $feature = $unleash->get('someFeature');
        $this->assertInstanceOf(FeatureFlag::class, $feature);
        $this->assertEquals(new FeatureFlag('someFeature', false), $feature);
        $this->assertArrayHasKey('enabled', $feature);
        $this->assertFalse($feature['enabled']);
        $this->assertFalse($feature->enabled());
        $this->assertTrue($feature->disabled());
    }
}