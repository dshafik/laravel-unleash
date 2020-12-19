<?php

namespace MikeFrancis\LaravelUnleash;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Contracts\Config\Repository as Config;

class Client extends GuzzleClient
{
    public function __construct(Config $config)
    {
        parent::__construct(
            [
                'base_uri' => $config->get('unleash.url'),
                'timeout' => $this->config->get('unleash.timeout'),
                'headers' => [
                    'UNLEASH-APPNAME' => $this->config->get('app.name'),
                    'UNLEASH-INSTANCEID' => $this->config->get('unleash.instanceId'),
                ],
            ],
        );
    }
}
