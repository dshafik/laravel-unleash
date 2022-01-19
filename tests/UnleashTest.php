<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use ErrorException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Tests\Stubs\ImplementedStrategy;
use MikeFrancis\LaravelUnleash\Tests\Stubs\NonImplementedStrategy;
use MikeFrancis\LaravelUnleash\Unleash;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Exception\JsonException;

class UnleashTest extends TestCase
{
    use MockClient;

    public function testFeaturesCanBeCached()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => true,
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->exactly(2))
            ->method('remember')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(null);
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(4))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(5))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(null);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->assertTrue($unleash->enabled($featureName));
        $this->assertTrue($unleash->enabled($featureName));
    }

    public function testFeaturesCacheFailoverEnabled()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => true,
                            ],
                        ],
                    ]
                )
            )
        );
        $this->mockHandler->append(
            new Response(500)
        );

        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);

        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(0.1);

        $cache->expects($this->at(0))
            ->method('remember')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );
        $cache->expects($this->at(1))
            ->method('forever')
            ->with('unleash.features.failover', new FeatureFlagCollection([
                new FeatureFlag($featureName, true)
            ]));

        // Request 2
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(4))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(5))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(0.1);

        $cache->expects($this->at(2))
            ->method('remember')
            ->willThrowException(new JsonException("Expected Failure: Testing"));

        $config->expects($this->at(6))
            ->method('get')
            ->with('unleash.cache.failover')
            ->willReturn(true);
        $cache->expects($this->at(3))
            ->method('get')
            ->with('unleash.features.failover')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->enabled($featureName), "Uncached Request");
        usleep(200);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request");
    }

    public function testFeaturesCacheFailoverDisabled()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => true,
                            ],
                        ],
                    ]
                )
            )
        );
        $this->mockHandler->append(
            new Response(500)
        );

        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);

        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(0.1);

        $cache->expects($this->at(0))
            ->method('remember')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );
        $cache->expects($this->at(1))
            ->method('forever')
            ->with('unleash.features.failover', new FeatureFlagCollection([
                new FeatureFlag($featureName, true)
            ]));

        // Request 2
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(4))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(5))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(0.1);

        $cache->expects($this->at(2))
            ->method('remember')
            ->willThrowException(new JsonException("Expected Failure: Testing"));

        $config->expects($this->at(6))
            ->method('get')
            ->with('unleash.cache.failover')
            ->willReturn(false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->enabled($featureName), "Uncached Request");
        usleep(200);
        $this->assertFalse($unleash->enabled($featureName), "Cached Request");
    }

    public function testFeaturesCacheFailoverEnabledIndependently()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => true,
                            ],
                        ],
                    ]
                )
            )
        );
        $this->mockHandler->append(
            new Response(500)
        );

        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);

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

        $cache->expects($this->at(0))
            ->method('forever')
            ->with('unleash.features.failover', new FeatureFlagCollection([
                new FeatureFlag($featureName, true)
            ]));

        // Request 2
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(4))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(false);
        $config->expects($this->at(5))
            ->method('get')
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');
        $config->expects($this->at(6))
            ->method('get')
            ->with('unleash.cache.failover')
            ->willReturn(true);

        $cache->expects($this->at(1))
            ->method('get')
            ->with('unleash.features.failover')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->assertTrue($unleash->enabled($featureName), "Uncached Request");

        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request");
    }

    public function testFeatureDetectionCanBeDisabled()
    {
        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertFalse($unleash->enabled('someFeature'));
    }

    public function testCanHandleErrorsFromUnleash()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(new Response(200, [], 'lol'));

        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')->willReturn(false);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->disabled($featureName));
    }

    public function testAll()
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
                            [
                                'name' => 'anotherFeature',
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

        $features = $unleash->all();
        $this->assertInstanceOf(FeatureFlagCollection::class, $features);
        $this->assertCount(2, $features);
        $this->assertEquals(new FeatureFlag('someFeature', true), $features[0]);
        $this->assertEquals(new FeatureFlag('anotherFeature', false), $features[1]);
    }

    public function testGet()
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
        $this->assertTrue($feature['enabled']);
    }

    public function testEnabled()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
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

        $this->assertTrue($unleash->enabled($featureName));
    }

    public function testEnabledWithValidStrategy()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => true,
                                'strategies' => [
                                    [
                                        'name' => 'testStrategy',
                                    ],
                                ],
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
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => ImplementedStrategy::class,
                ]
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->instance(Unleash::class, $unleash);

        $this->assertTrue($unleash->enabled($featureName));
    }

    public function testEnabledWithInvalidStrategy()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => true,
                                'strategies' => [
                                    [
                                        'name' => 'testStrategy',
                                    ],
                                ],
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
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'invalidTestStrategy' => ImplementedStrategy::class,
                ]
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->instance(Unleash::class, $unleash);

        $this->assertFalse($unleash->enabled($featureName));
    }

    public function testEnabledWithStrategyThatDoesNotImplementBaseStrategy()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => true,
                                'strategies' => [
                                    [
                                        'name' => 'testStrategy',
                                    ],
                                ],
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
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => NonImplementedStrategy::class,
                ]
            );

        $request = $this->createMock(Request::class);

        $this->expectException(ErrorException::class);
        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->instance(Unleash::class, $unleash);
        $unleash->enabled($featureName);
    }

    public function testDisabled()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
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

        $this->assertTrue($unleash->disabled($featureName));
    }
}
