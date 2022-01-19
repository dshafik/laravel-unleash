<?php

namespace MikeFrancis\LaravelUnleash\Testing\Fakes;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use MikeFrancis\LaravelUnleash\Unleash;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;

class UnleashFake
{
    /**
     * @var FeatureFlagCollection
     */
    protected $fakeFeatures = [];

    /**
     * @var Unleash
     */
    protected $unleash;

    public function __construct($unleash, FeatureFlagCollection $features)
    {
        $this->unleash = $unleash;
        $this->fakeFeatures = $features;
    }

    public function enabled($feature, ... $args)
    {
        if ($this->shouldFakeFeature($feature, $args)) {
            return $this->getFakeFeature($feature, $args)->enabled === true;
        } else {
            return $this->unleash->isFeatureEnabled($feature, ... $args);
        }
    }

    public function isFeatureEnabled($feature, ... $args)
    {
        return $this->enabled($feature, ... $args);
    }

    public function disabled($feature, ... $args)
    {
        return !$this->enabled($feature, ... $args);
    }

    public function isFeatureDisabled($feature, ... $args)
    {
        return $this->disabled($feature, ... $args);
    }

    public function get($name)
    {
        if ($this->shouldFakeFeature($name)) {
            return $this->getFakeFeature($name);
        } else {
            return $this->unleash->getFeature($name);
        }
    }

    public function getFeature($name)
    {
        return $this->get($name);
    }

    public function all()
    {
        $features = $this->unleash->getFeatures();

        $fakeFeatures = new Collection();
        $this->fakeFeatures->map(function($item) use ($fakeFeatures) {
            $name = $item->name;
            if (!$fakeFeatures->contains(function($item) use($name) {
                return $item->name === $name;
            })) {
                $fakeFeatures->push($item);
            }
        });

        return $features->merge($fakeFeatures);
    }

    public function getFeatures()
    {
        return $this->all();
    }

    public function __call($method, $args)
    {
        return $this->unleash->$method(... $args);
    }

    protected function shouldFakeFeature($feature, ?array $args = null)
    {
        return $this->fakeFeatures->first(function($item) use ($feature, $args) {
            if ($item->name === $feature) {
                return $args === null || $item->args === $args;
            }
            return false;
        }, false) !== false;
    }

    protected function getFakeFeature($feature, ?array $args = null)
    {
        return $this->fakeFeatures->first(function($item) use ($feature, $args) {
                if ($item->name === $feature) {
                    return $args === null || $item->args === $args;
                }
                return false;
            }, false);
    }
}