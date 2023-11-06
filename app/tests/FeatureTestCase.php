<?php

namespace Tests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class FeatureTestCase extends TestCase
{
    public Client $http;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->http = new Client([
            'base_uri' => $_ENV['BASE_URL']
        ]);
    }
}
